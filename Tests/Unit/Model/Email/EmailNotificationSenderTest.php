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

class EmailNotificationSenderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EmailNotificationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailNotificationManager;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EmailNotificationSender */
    private $emailNotificationSender;

    protected function setUp(): void
    {
        $this->emailNotificationManager = $this->createMock(EmailNotificationManager::class);
        $this->entityManager = $this->createMock(ObjectManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $this->emailNotificationSender = new EmailNotificationSender(
            $this->emailNotificationManager,
            $doctrine
        );
    }

    /**
     * @dataProvider sendAttendeesNotificationsDataProvider
     */
    public function testSendAttendeesNotifications(string $addNotificationMethod, string $expectedTemplateName)
    {
        $attendeeWithUser = $this->getEntity(
            AttendeeEntity::class,
            ['email' => 'foo@example.com', 'user' => (new User())->setEmail('foo@example.com')]
        );
        $attendeeWithoutUser1 = $this->getEntity(AttendeeEntity::class, ['email' => 'bar@example.com']);
        $attendeeWithoutUser2 = $this->getEntity(AttendeeEntity::class);

        $calendarEvent = $this->createCalendarEventWithAttendees([
            $attendeeWithUser,
            $attendeeWithoutUser1,
            $attendeeWithoutUser2
        ]);

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

    public function sendAttendeesNotificationsDataProvider(): array
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
     * @dataProvider sendSendInvitationStatusChangeNotificationsDataProvider
     */
    public function testSendInvitationStatusChangeNotifications(string $statusCode, string $expectedTemplateName)
    {
        $attendeeWithUser = $this->getEntity(
            AttendeeEntity::class,
            ['email' => 'foo@example.com', 'user' => (new User())->setEmail('foo@example.com')]
        );
        $attendeeWithoutUser1 = $this->getEntity(AttendeeEntity::class, ['email' => 'bar@example.com']);
        $attendeeWithoutUser2 = $this->getEntity(AttendeeEntity::class);

        $calendarEvent = $this->createCalendarEventWithAttendees([
            $attendeeWithUser,
            $attendeeWithoutUser1,
            $attendeeWithoutUser2
        ]);

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

        $calendarEvent = $this->createCalendarEventWithAttendees([
            $attendeeWithUser,
            $attendeeWithoutUser1,
            $attendeeWithoutUser2
        ]);

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

    public function sendSendInvitationStatusChangeNotificationsDataProvider(): array
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

    private function createCalendarEventWithAttendees(array $attendeesData): CalendarEvent
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

    private function getChildEventByEmail(CalendarEvent $calendarEvent, string $email): CalendarEvent
    {
        foreach ($calendarEvent->getChildEvents() as $child) {
            if ($child->getCalendar()->getOwner()->getEmail() === $email) {
                return $child;
            }
        }
        $this->fail('Failed to get child event by email of calendar owner user.');
    }
}
