<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Email;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationSender;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class EmailNotificationSenderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EmailNotificationManager
     */
    protected $emailNotificationManager;

    /**
     * @var EmailNotificationSender
     */
    protected $emailNotificationSender;

    protected function setUp(): void
    {
        $this->emailNotificationManager = $this
            ->getMockBuilder(EmailNotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->createMock(ObjectManager::class);
        /** @var ManagerRegistry|MockObject $managerRegistry */
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
        $attendeeWithUser = $this->getEntity(
            AttendeeEntity::class,
            ['email' => 'foo@example.com', 'user' => (new User())->setEmail('foo@example.com')]
        );
        $attendeeWithoutUser1 = $this->getEntity(AttendeeEntity::class, ['email' => 'bar@example.com']);
        $attendeeWithoutUser2 = $this->getEntity(AttendeeEntity::class);

        $calendarEvent = $this->createCalendarEventWithAttendees(
            [
                $attendeeWithUser,
                $attendeeWithoutUser1,
                $attendeeWithoutUser2
            ]
        );

        $this->emailNotificationSender->$addNotificationMethod(
            $calendarEvent,
            [$attendeeWithUser, $attendeeWithoutUser1, $attendeeWithoutUser2]
        );

        $expectedNotifications = [
            new TemplateEmailNotification(
                new EmailTemplateCriteria($expectedTemplateName),
                [$attendeeWithUser],
                $this->getChildEventByEmail($calendarEvent, 'foo@example.com')
            ),
            new TemplateEmailNotification(
                new EmailTemplateCriteria($expectedTemplateName),
                [$attendeeWithoutUser1],
                $calendarEvent
            )
        ];

        $this->emailNotificationManager->expects($this->once())
            ->method('process')
            ->with($expectedNotifications);

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
        $attendeeWithUser = $this->getEntity(
            AttendeeEntity::class,
            ['email' => 'foo@example.com', 'user' => (new User())->setEmail('foo@example.com')]
        );
        $attendeeWithoutUser1 = $this->getEntity(AttendeeEntity::class, ['email' => 'bar@example.com']);
        $attendeeWithoutUser2 = $this->getEntity(AttendeeEntity::class);

        $calendarEvent = $this->createCalendarEventWithAttendees(
            [
                $attendeeWithUser,
                $attendeeWithoutUser1,
                $attendeeWithoutUser2
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

        $expectedNotifications = [
            new TemplateEmailNotification(
                new EmailTemplateCriteria($expectedTemplateName),
                [$ownerUser],
                $childCalendarEvent
            ),
        ];

        $this->emailNotificationManager->expects($this->once())
            ->method('process')
            ->with($expectedNotifications);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->emailNotificationSender->sendAddedNotifications();
    }

    public function testDeleteChildCalendarEventNotifications()
    {
        $attendeeWithUser = $this->getEntity(
            AttendeeEntity::class,
            ['email' => 'foo@example.com', 'user' => (new User())->setEmail('foo@example.com')]
        );
        $attendeeWithoutUser1 = $this->getEntity(AttendeeEntity::class, ['email' => 'bar@example.com']);
        $attendeeWithoutUser2 = $this->getEntity(AttendeeEntity::class);

        $calendarEvent = $this->createCalendarEventWithAttendees(
            [
                $attendeeWithUser,
                $attendeeWithoutUser1,
                $attendeeWithoutUser2
            ]
        );

        $ownerUser = $calendarEvent->getCalendar()->getOwner();
        $ownerUser->setEmail('owner@example.com');

        $childCalendarEvent = $this->getChildEventByEmail($calendarEvent, 'foo@example.com');

        $this->emailNotificationSender->addDeleteChildCalendarEventNotifications(
            $childCalendarEvent,
            $ownerUser
        );

        $expectedNotifications = [
            new TemplateEmailNotification(
                new EmailTemplateCriteria(EmailNotificationSender::NOTIFICATION_TEMPLATE_DELETE_CHILD),
                [$ownerUser],
                $childCalendarEvent
            ),
        ];

        $this->emailNotificationManager->expects($this->once())
            ->method('process')
            ->with($expectedNotifications);

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

        /** @var Attendee $attendee */
        foreach ($attendeesData as $attendee) {
            if (null !== $attendee->getUser()) {
                $attendeeCalendar = new Calendar();
                $attendeeCalendar->setOwner($attendee->getUser());

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
