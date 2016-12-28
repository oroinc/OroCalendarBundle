<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Email;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Model\Email\EmailNotification;
use Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationSender;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;

use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;

class EmailNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailNotificationManager;

    /**
     * @var EmailNotificationSender
     */
    protected $emailNotificationSender;

    protected function setUp()
    {
        $this->emailNotificationManager = $this
            ->getMockBuilder(EmailNotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->createMock(ObjectManager::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->entityManager));

        $this->emailNotificationSender = new EmailNotificationSender(
            $this->emailNotificationManager,
            $managerRegistry
        );
    }

    /**
     * @param string $addNotificationMethod
     * @param string $expectedTemplateName
     *
     * @dataProvider sendAttendeesNotificationsDataProvider
     */
    public function testSendAttendeesNotifications($addNotificationMethod, $expectedTemplateName)
    {
        $calendarEvent = $this->createCalendarEventWithAttendees(
            [
                ['email' => 'foo@example.com', 'hasUser' => true],
                ['email' => 'bar@example.com', 'hasUser' => false],
                ['email' => null, 'hasUser' => false],
            ]
        );

        $this->emailNotificationSender->$addNotificationMethod(
            $calendarEvent,
            $calendarEvent->getAttendees()->toArray()
        );

        $this->emailNotificationManager->expects($this->exactly(2))
            ->method('process');

        $this->emailNotificationManager->expects($this->at(0))
            ->method('process')
            ->with(
                $this->getChildEventByEmail($calendarEvent, 'foo@example.com'),
                $this->notificationEqualTo(
                    $this->getChildEventByEmail($calendarEvent, 'foo@example.com'),
                    'foo@example.com',
                    $expectedTemplateName
                )
            );

        $this->emailNotificationManager->expects($this->at(1))
            ->method('process')
            ->with(
                $calendarEvent,
                $this->notificationEqualTo(
                    $calendarEvent,
                    'bar@example.com',
                    $expectedTemplateName
                )
            );

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->emailNotificationSender->sendAddedNotifications();
    }

    /**
     * @return array
     */
    public function sendAttendeesNotificationsDataProvider()
    {
        return [
            'invite' => [
                'addNotificationMethod' => 'addInviteNotifications',
                'expectedTemplateName' => EmailNotificationSender::NOTIFICATION_TEMPLATE_INVITE,
            ],
            'cancel' => [
                'addNotificationMethod' => 'addCancelNotifications',
                'expectedTemplateName' => EmailNotificationSender::NOTIFICATION_TEMPLATE_CANCEL,
            ],
            'un_invite' => [
                'addNotificationMethod' => 'addUnInviteNotifications',
                'expectedTemplateName' => EmailNotificationSender::NOTIFICATION_TEMPLATE_UN_INVITE,
            ],
            'update' => [
                'addNotificationMethod' => 'addUpdateNotifications',
                'expectedTemplateName' => EmailNotificationSender::NOTIFICATION_TEMPLATE_UPDATE,
            ],
        ];
    }

    /**
     * @param string $statusCode
     * @param string $expectedTemplateName
     *
     * @dataProvider sendSendInvitationStatusChangeNotificationsDataProvider
     */
    public function testSendInvitationStatusChangeNotifications($statusCode, $expectedTemplateName)
    {
        $calendarEvent = $this->createCalendarEventWithAttendees(
            [
                ['email' => 'foo@example.com', 'hasUser' => true],
                ['email' => 'bar@example.com', 'hasUser' => false],
                ['email' => null, 'hasUser' => false],
            ]
        );

        $ownerUser = $calendarEvent->getCalendar()->getOwner();
        $ownerUser->setEmail('owner@example.com');

        $childCalendarEvent = $this->getChildEventByEmail($calendarEvent, 'foo@example.com');

        $this->emailNotificationSender->addInvitationStatusChangeNotifications(
            $childCalendarEvent,
            $ownerUser,
            $statusCode
        );

        $this->emailNotificationManager->expects($this->once())
            ->method('process')
            ->with(
                $childCalendarEvent,
                $this->notificationEqualTo(
                    $childCalendarEvent,
                    'owner@example.com',
                    $expectedTemplateName
                )
            );

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->emailNotificationSender->sendAddedNotifications();
    }

    public function testDeleteChildCalendarEventNotifications()
    {
        $calendarEvent = $this->createCalendarEventWithAttendees(
            [
                ['email' => 'foo@example.com', 'hasUser' => true],
                ['email' => 'bar@example.com', 'hasUser' => false],
                ['email' => null, 'hasUser' => false],
            ]
        );

        $ownerUser = $calendarEvent->getCalendar()->getOwner();
        $ownerUser->setEmail('owner@example.com');

        $childCalendarEvent = $this->getChildEventByEmail($calendarEvent, 'foo@example.com');

        $this->emailNotificationSender->addDeleteChildCalendarEventNotifications(
            $childCalendarEvent,
            $ownerUser
        );

        $this->emailNotificationManager->expects($this->once())
            ->method('process')
            ->with(
                $childCalendarEvent,
                $this->notificationEqualTo(
                    $childCalendarEvent,
                    'owner@example.com',
                    EmailNotificationSender::NOTIFICATION_TEMPLATE_DELETE_CHILD
                )
            );

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->emailNotificationSender->sendAddedNotifications();
    }

    /**
     * @return array
     */
    public function sendSendInvitationStatusChangeNotificationsDataProvider()
    {
        return [
            'accepted' => [
                'statusCode' => Attendee::STATUS_ACCEPTED,
                'expectedTemplateName' => EmailNotificationSender::NOTIFICATION_TEMPLATE_STATUS_ACCEPTED,
            ],
            'declined' => [
                'statusCode' => Attendee::STATUS_DECLINED,
                'expectedTemplateName' => EmailNotificationSender::NOTIFICATION_TEMPLATE_STATUS_DECLINED,
            ],
            'tentative' => [
                'statusCode' => Attendee::STATUS_TENTATIVE,
                'expectedTemplateName' => EmailNotificationSender::NOTIFICATION_TEMPLATE_STATUS_TENTATIVE,
            ],
        ];
    }

    /**
     * @param $expectedEntity
     * @param $expectedEmail
     * @param $expectedTemplate
     * @return \PHPUnit_Framework_Constraint_Callback
     */
    public function notificationEqualTo($expectedEntity, $expectedEmail, $expectedTemplate)
    {
        return $this->callback(
            function ($notifications) use ($expectedEntity, $expectedEmail, $expectedTemplate) {
                $this->assertInternalType('array', $notifications);
                $this->assertCount(1, $notifications);
                /** @var EmailNotification $notification */
                $notification = $notifications[0];
                $this->assertInstanceOf(EmailNotification::class, $notification);
                $this->assertSame($expectedEntity, $notification->getEntity());
                $this->assertEquals([$expectedEmail], $notification->getRecipientEmails());
                $this->assertAttributeEquals($expectedTemplate, 'templateName', $notification);

                return true;
            }
        );
    }

    /**
     * Creates calendar event with attendees.
     *
     * @param array $attendeesData Data for attendees.
     * @return CalendarEvent Main calendar event
     */
    public function createCalendarEventWithAttendees(array $attendeesData)
    {
        $ownerUser = new User();
        $ownerCalendar = new Calendar();
        $ownerCalendar->setOwner($ownerUser);

        $ownerCalendarEvent = new CalendarEvent();
        $ownerCalendarEvent->setCalendar($ownerCalendar);

        foreach ($attendeesData as $attendeeData) {
            $attendee = new Attendee();
            $attendee->setEmail($attendeeData['email']);
            if (!empty($attendeeData['hasUser'])) {
                $attendeeUser = new User();
                $attendeeUser->setEmail($attendeeData['email']);

                $attendee->setUser($attendeeUser);

                $attendeeCalendar = new Calendar();
                $attendeeCalendar->setOwner($attendeeUser);

                $attendeeCalendarEvent = new CalendarEvent();
                $attendeeCalendarEvent->setCalendar($attendeeCalendar);

                $ownerCalendarEvent->addChildEvent($attendeeCalendarEvent);
            }
            $ownerCalendarEvent->addAttendee($attendee);
        }

        return $ownerCalendarEvent;
    }

    /**
     * @param CalendarEvent $calendarEvent
     * @param string $email
     * @return CalendarEvent
     */
    protected function getChildEventByEmail(CalendarEvent $calendarEvent, $email)
    {
        foreach ($calendarEvent->getChildEvents() as $child) {
            if ($child->getCalendar()->getOwner()->getEmail() === $email) {
                return $child;
            }
        }
        $this->fail('Failed to get child event by email of calendar owner user.');
    }
}
