<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationSender;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NotificationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailNotificationSender|\PHPUnit\Framework\MockObject\MockObject */
    private $emailNotificationSender;

    /** @var NotificationManager */
    private $notificationManager;

    protected function setUp(): void
    {
        $this->emailNotificationSender = $this->createMock(EmailNotificationSender::class);

        $this->notificationManager = new NotificationManager($this->emailNotificationSender);
    }

    public function testSendNoNotificationOnCreateOfSystemCalendarEvent()
    {
        $systemCalendar = new SystemCalendar();
        $attendee = new Attendee();

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setSystemCalendar($systemCalendar)
            ->addAttendee($attendee);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onCreate($calendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testSendNotificationOnCreateOfRegularCalendarEvent()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $this->emailNotificationSender->expects($this->once())
            ->method('addInviteNotifications')
            ->with($calendarEvent, [$attendee]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onCreate($calendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testSendCancelNotificationOnCreateOfCancelledExceptionInRecurringCalendarEvent()
    {
        $recurringCalendarEvent = new CalendarEvent();

        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCancelled(true)
            ->setCalendar($calendar)
            ->setRecurringEvent($recurringCalendarEvent)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $this->emailNotificationSender->expects($this->once())
            ->method('addCancelNotifications')
            ->with($calendarEvent, [$attendee]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onCreate($calendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testFailOnCreateOfCancelledExceptionWithoutRecurringCalendarEvent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Inconsistent state of calendar event: Cancelled event should have relation to recurring event.'
        );

        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee = new Attendee();

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCancelled(true)
            ->setCalendar($calendar)
            ->setRecurringEvent(null)
            ->addAttendee($attendee);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onCreate($calendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testSendCancelNotificationOnUpdateOfCalendarOfEventFromUserToSystem()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCalendar($calendar)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $systemCalendar = new SystemCalendar();
        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setCalendar(null)
            ->setSystemCalendar($systemCalendar);

        $this->emailNotificationSender->expects($this->once())
            ->method('addCancelNotifications')
            ->with($originalCalendarEvent, [$attendee]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendNoNotificationOnUpdateOfSystemCalendarEvent()
    {
        $systemCalendar = new SystemCalendar();

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setSystemCalendar($systemCalendar);

        $calendarEvent = clone $originalCalendarEvent;

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendCancelNotificationOnUpdateForCancelledExceptionInRecurringCalendarEvent()
    {
        $recurringCalendarEvent = new CalendarEvent();
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCancelled(false)
            ->setRecurringEvent($recurringCalendarEvent)
            ->setCalendar($calendar);

        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setCancelled(true)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $this->emailNotificationSender->expects($this->once())
            ->method('addCancelNotifications')
            ->with($calendarEvent, [$attendee]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendCancelNotificationOnUpdateForCancelledExceptionInRecurringCalendarEventOfAttendee()
    {
        $recurringCalendarEvent = new CalendarEvent(1);
        $attendeeRecurringCalendarEvent = new CalendarEvent(2);
        $ownerUser = new User(1);
        $attendeeUser = new User(2);
        $calendar = new Calendar(1);
        $calendar->setOwner($ownerUser);
        $attendeeCalendar = new Calendar(2);
        $attendeeCalendar->setOwner($attendeeUser);
        $attendee = new Attendee(1);
        $attendee->setUser($attendeeUser);
        $ownerAttendee = new Attendee(2);
        $ownerAttendee->setUser($ownerUser);

        $ownerCalendarEventException = new CalendarEvent();
        $ownerCalendarEventException
            ->setCancelled(false)
            ->setRecurringEvent($recurringCalendarEvent)
            ->setCalendar($calendar)
            ->addAttendee($ownerAttendee)
            ->setRelatedAttendee($ownerAttendee);

        $originalAttendeeCalendarEventException = new CalendarEvent();
        $originalAttendeeCalendarEventException
            ->setCancelled(false)
            ->setParent($ownerCalendarEventException)
            ->setRecurringEvent($attendeeRecurringCalendarEvent)
            ->setRelatedAttendee($attendee)
            ->setCalendar($attendeeCalendar);

        $attendeeCalendarEventException = clone $originalAttendeeCalendarEventException;
        $attendeeCalendarEventException
            ->setCancelled(true)
            ->setRelatedAttendee($attendee);

        $this->emailNotificationSender->expects($this->once())
            ->method('addCancelNotifications')
            ->with($attendeeCalendarEventException, [$attendee]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onUpdate(
            $attendeeCalendarEventException,
            $originalAttendeeCalendarEventException,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendNoNotificationOnUpdateForCancelledExceptionInRecurringCalendarEventIfItWasCancelledBefore()
    {
        $recurringCalendarEvent = new CalendarEvent();
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCancelled(true)
            ->setRecurringEvent($recurringCalendarEvent)
            ->setCalendar($calendar)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setCancelled(true);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testFailOnUpdateForCancelledExceptionWithoutRecurringCalendarEvent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Inconsistent state of calendar event: Cancelled event should have relation to recurring event.'
        );

        $recurringCalendarEvent = new CalendarEvent();
        $calendar = new Calendar();
        $attendee = new Attendee();

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCancelled(false)
            ->setRecurringEvent($recurringCalendarEvent)
            ->setCalendar($calendar)
            ->addAttendee($attendee);

        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setRecurringEvent(null)
            ->setCancelled(true);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onCreate(
            $calendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendInviteNotificationOnUpdateForReactivatedEventExceptionWhenItWasCancelledBefore()
    {
        $recurringCalendarEvent = new CalendarEvent();
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCancelled(true)
            ->setRecurringEvent($recurringCalendarEvent)
            ->setCalendar($calendar);

        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setCancelled(false)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $this->emailNotificationSender->expects($this->once())
            ->method('addInviteNotifications')
            ->with($calendarEvent, [$attendee]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendAllUpdateInviteCancelNotificationOnUpdateForAllNotChangedAddedAndRemovedAttendees()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $notChangedAttendee1 = (new Attendee(1))->setDisplayName('Not Attendee Changed 1');
        $notChangedAttendee2 = (new Attendee(2))->setDisplayName('Not Attendee Changed 2');
        $addedAttendee1 = (new Attendee(3))->setDisplayName('Added Attendee 1');
        $addedAttendee2 = (new Attendee(4))->setDisplayName('Added Attendee 2');
        $removedAttendee1 = (new Attendee(5))->setDisplayName('Removed Attendee 1');
        $removedAttendee2 = (new Attendee(6))->setDisplayName('Removed Attendee 2');

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $notChangedAttendee1,
                        $notChangedAttendee2,
                        $removedAttendee1,
                        $removedAttendee2
                    ]
                )
            );

        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setAttendees(
                new ArrayCollection(
                    [
                        $notChangedAttendee1,
                        $notChangedAttendee2,
                        $addedAttendee1,
                        $addedAttendee2
                    ]
                )
            );

        $this->emailNotificationSender->expects($this->once())
            ->method('addUpdateNotifications')
            ->with($calendarEvent, [$notChangedAttendee1, $notChangedAttendee2]);

        $this->emailNotificationSender->expects($this->once())
            ->method('addInviteNotifications')
            ->with($calendarEvent, [$addedAttendee1, $addedAttendee2]);

        $this->emailNotificationSender->expects($this->once())
            ->method('addUnInviteNotifications')
            ->with($originalCalendarEvent, [$removedAttendee1, $removedAttendee2]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendCancelNotificationOnUpdateForExtraAttendeesInClearedExceptions()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee1 = (new Attendee(1))->setDisplayName('Existed Attendee 1');
        $attendee2 = (new Attendee(2))->setDisplayName('Existed Attendee 2');
        $extraAttendee1 = (new Attendee(3))->setDisplayName('Extra Attendee 1');
        $extraAttendee2 = (new Attendee(4))->setDisplayName('Extra Attendee 2');
        $extraAttendee3 = (new Attendee(5))->setDisplayName('Extra Attendee 3');
        $extraAttendee4 = (new Attendee(6))->setDisplayName('Extra Attendee 4');

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $attendee1,
                        $attendee2,
                    ]
                )
            );

        $calendarEventException1 = new CalendarEvent();
        $calendarEventException1
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $attendee1,
                        $attendee2,
                        $extraAttendee1,
                        $extraAttendee2
                    ]
                )
            );

        $calendarEventException2 = new CalendarEvent();
        $calendarEventException2
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $extraAttendee3
                    ]
                )
            );

        $calendarEventException3 = new CalendarEvent();
        $calendarEventException3
            ->setCalendar($calendar)
            ->setCancelled(true)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $extraAttendee4
                    ]
                )
            );

        $calendarEvent
            ->addRecurringEventException($calendarEventException1)
            ->addRecurringEventException($calendarEventException2);

        $originalCalendarEvent = clone $calendarEvent;
        $calendarEvent->getRecurringEventExceptions()->clear();

        $this->emailNotificationSender->expects($this->exactly(2))
            ->method('addCancelNotifications')
            ->withConsecutive(
                [$calendarEventException1, [$extraAttendee1, $extraAttendee2]],
                [$calendarEventException2, [$extraAttendee3]]
            );

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendNoNotificationOnUpdateWithNoneNotificationStrategy()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $notChangedAttendee1 = (new Attendee(1))->setDisplayName('Not Attendee Changed 1');
        $notChangedAttendee2 = (new Attendee(2))->setDisplayName('Not Attendee Changed 2');
        $addedAttendee1 = (new Attendee(3))->setDisplayName('Added Attendee 1');
        $addedAttendee2 = (new Attendee(4))->setDisplayName('Added Attendee 2');
        $removedAttendee1 = (new Attendee(5))->setDisplayName('Removed Attendee 1');
        $removedAttendee2 = (new Attendee(6))->setDisplayName('Removed Attendee 2');

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $notChangedAttendee1,
                        $notChangedAttendee2,
                        $removedAttendee1,
                        $removedAttendee2
                    ]
                )
            );

        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setAttendees(
                new ArrayCollection(
                    [
                        $notChangedAttendee1,
                        $notChangedAttendee2,
                        $addedAttendee1,
                        $addedAttendee2
                    ]
                )
            );

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::NONE_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendOnlyInviteAndCancelNotificationOnUpdateWhenNotifyExistedUsersIsFalse()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $notChangedAttendee1 = (new Attendee(1))->setDisplayName('Not Attendee Changed 1');
        $notChangedAttendee2 = (new Attendee(2))->setDisplayName('Not Attendee Changed 2');
        $addedAttendee1 = (new Attendee(3))->setDisplayName('Added Attendee 1');
        $addedAttendee2 = (new Attendee(4))->setDisplayName('Added Attendee 2');
        $removedAttendee1 = (new Attendee(5))->setDisplayName('Removed Attendee 1');
        $removedAttendee2 = (new Attendee(6))->setDisplayName('Removed Attendee 2');

        $originalCalendarEvent = new CalendarEvent();
        $originalCalendarEvent
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $notChangedAttendee1,
                        $notChangedAttendee2,
                        $removedAttendee1,
                        $removedAttendee2
                    ]
                )
            );

        $calendarEvent = clone $originalCalendarEvent;
        $calendarEvent
            ->setAttendees(
                new ArrayCollection(
                    [
                        $notChangedAttendee1,
                        $notChangedAttendee2,
                        $addedAttendee1,
                        $addedAttendee2
                    ]
                )
            );

        $this->emailNotificationSender->expects($this->never())
            ->method('addUpdateNotifications');

        $this->emailNotificationSender->expects($this->once())
            ->method('addInviteNotifications')
            ->with($calendarEvent, [$addedAttendee1, $addedAttendee2]);

        $this->emailNotificationSender->expects($this->once())
            ->method('addUnInviteNotifications')
            ->with($originalCalendarEvent, [$removedAttendee1, $removedAttendee2]);

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onUpdate(
            $calendarEvent,
            $originalCalendarEvent,
            NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendNoNotificationsOnChangeInvitationStatusForCalendarEventOwner()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $attendee = new Attendee();
        $attendee->setUser($ownerUser);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->addAttendee($attendee)
            ->setRelatedAttendee($attendee);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onChangeInvitationStatus(
            $calendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testFailOnChangeInvitationStatusForCalendarEventWithoutRelatedAttendee()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Inconsistent state of calendar event: Related attendee is not exist.'
        );

        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $attendee = new Attendee();
        $attendee->setUser($ownerUser);

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent->addAttendee($attendee);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setParent($parentCalendarEvent)
            ->setCalendar($calendar);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onChangeInvitationStatus(
            $calendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testFailOnChangeInvitationStatusForCalendarEventWithoutOwnerOfParentEvent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Inconsistent state of calendar event: Owner user is not accessible.'
        );

        $ownerUser = new User();

        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $attendee = new Attendee();
        $attendee->setUser($ownerUser);

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent->addAttendee($attendee);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setParent($parentCalendarEvent)
            ->setCalendar($calendar)
            ->setRelatedAttendee($attendee);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onChangeInvitationStatus(
            $calendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendNotificationOnChangeInvitationStatus()
    {
        $ownerUser = new User();
        $parentOwnerUser = new User();

        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $parentCalendar = new Calendar();
        $parentCalendar->setOwner($parentOwnerUser);

        $statusCode = Attendee::STATUS_ACCEPTED;
        $status = $this->createMock(AbstractEnumValue::class);
        $status->expects($this->once())
            ->method('getId')
            ->willReturn($statusCode);

        $attendee = new Attendee();
        $attendee->setUser($ownerUser);
        $attendee->setStatus($status);

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent
            ->addAttendee($attendee)
            ->setCalendar($parentCalendar);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setParent($parentCalendarEvent)
            ->setCalendar($calendar)
            ->setRelatedAttendee($attendee);

        $this->emailNotificationSender->expects($this->once())
            ->method('addInvitationStatusChangeNotifications')
            ->with(
                $calendarEvent,
                $parentOwnerUser,
                $statusCode
            );

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onChangeInvitationStatus(
            $calendarEvent,
            NotificationManager::ALL_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendNoNotificationsOnChangeInvitationStatusWithNoneNotificationStrategy()
    {
        $ownerUser = new User();
        $parentOwnerUser = new User();

        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $parentCalendar = new Calendar();
        $parentCalendar->setOwner($parentOwnerUser);

        $status = $this->createMock(AbstractEnumValue::class);

        $attendee = new Attendee();
        $attendee->setUser($ownerUser);
        $attendee->setStatus($status);

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent
            ->addAttendee($attendee)
            ->setCalendar($parentCalendar);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setParent($parentCalendarEvent)
            ->setCalendar($calendar)
            ->setRelatedAttendee($attendee);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onChangeInvitationStatus(
            $calendarEvent,
            NotificationManager::NONE_NOTIFICATIONS_STRATEGY
        );
    }

    public function testFailOnDeleteOfParentCalendarEventWithoutOwner()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Inconsistent state of calendar event: Owner user is not accessible.'
        );

        $calendar = new Calendar();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCalendar($calendar);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onDelete($calendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testFailOnDeleteOfChildCalendarEventWithoutOwner()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Inconsistent state of calendar event: Owner user is not accessible.'
        );

        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $parentCalendar = new Calendar();

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent
            ->setCalendar($parentCalendar);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->setParent($parentCalendarEvent);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onDelete($calendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testSendCancelNotificationOnDeleteOfParentCalendarEvent()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $parentOwnerUser = new User();
        $parentCalendar = new Calendar();
        $parentCalendar->setOwner($parentOwnerUser);

        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent
            ->setCalendar($parentCalendar)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->setParent($parentCalendarEvent);

        $this->emailNotificationSender->expects($this->once())
            ->method('addCancelNotifications')
            ->with(
                $parentCalendarEvent,
                [$attendee]
            );

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onDelete($parentCalendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testSendCancelNotificationOnDeleteOfRecurringCalendarEventWithExtraAttendeesInExceptions()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);
        $attendee1 = (new Attendee(1))->setDisplayName('Existed Attendee 1');
        $attendee2 = (new Attendee(2))->setDisplayName('Existed Attendee 2');
        $extraAttendee1 = (new Attendee(3))->setDisplayName('Extra Attendee 1');
        $extraAttendee2 = (new Attendee(4))->setDisplayName('Extra Attendee 2');
        $extraAttendee3 = (new Attendee(5))->setDisplayName('Extra Attendee 3');
        $extraAttendee4 = (new Attendee(6))->setDisplayName('Extra Attendee 4');

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $attendee1,
                        $attendee2,
                    ]
                )
            );

        $calendarEventException1 = new CalendarEvent();
        $calendarEventException1
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $attendee1,
                        $attendee2,
                        $extraAttendee1,
                        $extraAttendee2
                    ]
                )
            );

        $calendarEventException2 = new CalendarEvent();
        $calendarEventException2
            ->setCalendar($calendar)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $extraAttendee3
                    ]
                )
            );

        $calendarEventException3 = new CalendarEvent();
        $calendarEventException3
            ->setCalendar($calendar)
            ->setCancelled(true)
            ->setAttendees(
                new ArrayCollection(
                    [
                        $extraAttendee4
                    ]
                )
            );

        $calendarEvent
            ->addRecurringEventException($calendarEventException1)
            ->addRecurringEventException($calendarEventException2);

        $this->emailNotificationSender->expects($this->exactly(3))
            ->method('addCancelNotifications')
            ->withConsecutive(
                [$calendarEvent, [$attendee1, $attendee2]],
                [$calendarEventException1, [$extraAttendee1, $extraAttendee2]],
                [$calendarEventException2, [$extraAttendee3]]
            );

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onDelete(
            $calendarEvent,
            NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
        );
    }

    public function testSendNoNotificationsOnDeleteOfParentCalendarEventWithNoneNotificationStrategy()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $parentOwnerUser = new User();
        $parentCalendar = new Calendar();
        $parentCalendar->setOwner($parentOwnerUser);

        $attendee = new Attendee();
        $ownerAttendee = new Attendee();
        $ownerAttendee->setUser($ownerUser);

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent
            ->setCalendar($parentCalendar)
            ->addAttendee($attendee)
            ->addAttendee($ownerAttendee);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->setParent($parentCalendarEvent);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onDelete($parentCalendarEvent, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);
    }

    public function testSendCancelNotificationOnDeleteOfChildCalendarEvent()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $parentOwnerUser = new User();
        $parentCalendar = new Calendar();
        $parentCalendar->setOwner($parentOwnerUser);

        $attendee = new Attendee();

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent
            ->setCalendar($parentCalendar)
            ->addAttendee($attendee);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->setParent($parentCalendarEvent);

        $this->emailNotificationSender->expects($this->once())
            ->method('addDeleteChildCalendarEventNotifications')
            ->with(
                $calendarEvent,
                $ownerUser
            );

        $this->emailNotificationSender->expects($this->once())
            ->method('sendAddedNotifications');

        $this->notificationManager->onDelete($calendarEvent, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);
    }

    public function testSendNoNotificationsOnDeleteOfChildCalendarEventWithNoneNotificationStrategy()
    {
        $ownerUser = new User();
        $calendar = new Calendar();
        $calendar->setOwner($ownerUser);

        $parentOwnerUser = new User();
        $parentCalendar = new Calendar();
        $parentCalendar->setOwner($parentOwnerUser);

        $attendee = new Attendee();

        $parentCalendarEvent = new CalendarEvent();
        $parentCalendarEvent
            ->setCalendar($parentCalendar)
            ->addAttendee($attendee);

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setCalendar($calendar)
            ->setParent($parentCalendarEvent);

        $this->emailNotificationSender->expects($this->never())
            ->method($this->anything());

        $this->notificationManager->onDelete($calendarEvent, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);
    }
}
