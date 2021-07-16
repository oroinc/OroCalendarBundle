<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationSender;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Can decide to send notification in case when event is created, updated or deleted.
 *
 * Has next responsibilities:
 * - Identify what kind of notification should be send.
 * - Identify attendees receivers of the notification.
 * - elegate sending of notification to email notification sender.
 *
 * @see \Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationProcessor
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NotificationManager
{
    /**
     * Send notification to each attendee of the event.
     */
    const ALL_NOTIFICATIONS_STRATEGY = 'all';

    /**
     * Do not send any notifications.
     */
    const NONE_NOTIFICATIONS_STRATEGY = 'none';

    /**
     * Send notification only to added or deleted attendees. Used only in onUpdate method.
     *
     * @see NotificationManager::onUpdate
     */
    const ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY = 'added_or_deleted';

    /**
     * @var EmailNotificationSender
     */
    protected $emailNotificationSender;

    /**
     * EmailNotificationHandler constructor.
     */
    public function __construct(EmailNotificationSender $processor)
    {
        $this->emailNotificationSender = $processor;
    }

    /**
     * Get list of all supported strategies.
     *
     * @return array
     */
    public function getSupportedStrategies()
    {
        return [
            static::ALL_NOTIFICATIONS_STRATEGY,
            static::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY,
            static::NONE_NOTIFICATIONS_STRATEGY
        ];
    }

    /**
     * Handle calendar event notification on create of the event.
     *
     * @param CalendarEvent $calendarEvent  Actual calendar event.
     * @param string        $strategy       Could be one of next values: none, all.
     * @throws \InvalidArgumentException When calendar event is cancelled and has no relation to recurring event.
     *
     * @see NotificationManager::ALL_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
     */
    public function onCreate(CalendarEvent $calendarEvent, $strategy)
    {
        /**
         * Notifications should not be sent in next cases:
         * 1) Strategy is 'none'
         * 2) Calendar Event belongs to System Calendar
         */
        if ($strategy === static::NONE_NOTIFICATIONS_STRATEGY || $calendarEvent->getSystemCalendar()) {
            return;
        }

        if ($calendarEvent->isCancelled()) {
            // A new recurring calendar event exception was created as cancelled.
            if (!$calendarEvent->getRecurringEvent()) {
                // Check the event for consistency, since this case is valid only for exceptions of recurring event.
                throw new \InvalidArgumentException(
                    'Inconsistent state of calendar event: Cancelled event should have relation to recurring event.'
                );
            }
            // Cancel notification should be send.
            $this->addCancelNotifications(
                $calendarEvent,
                $this->getCalendarOwnerUser($calendarEvent),
                $calendarEvent->getAttendees()->toArray()
            );
            $this->sendAddedNotifications();
        } else {
            // A new event was created, invite notification should be send.
            $this->addInviteNotifications(
                $calendarEvent,
                $this->getCalendarOwnerUser($calendarEvent),
                $calendarEvent->getAttendees()->toArray()
            );
            $this->sendAddedNotifications();
        }
    }

    /**
     * Get owner of calendar event.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return User
     *
     * @throws \LogicException When event has no owner user.
     */
    protected function getCalendarOwnerUser(CalendarEvent $calendarEvent)
    {
        if (!$calendarEvent->getCalendar() || !$calendarEvent->getCalendar()->getOwner()) {
            throw new \InvalidArgumentException(
                'Inconsistent state of calendar event: Owner user is not accessible.'
            );
        }

        return $calendarEvent->getCalendar()->getOwner();
    }

    /**
     * Adds cancel notifications for attendees of calendar event except owner of the event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param User          $ownerUser      Owner user of main calendar event.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    protected function addCancelNotifications(
        CalendarEvent $calendarEvent,
        User $ownerUser,
        array $attendees
    ) {
        $filteredAttendees = $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees);
        if ($filteredAttendees) {
            $this->emailNotificationSender->addCancelNotifications(
                $calendarEvent,
                $filteredAttendees
            );
        }
    }

    /**
     * Returns list of attendees without attendee related to user.
     *
     * @param User          $ownerUser  Owner user of main calendar event.
     * @param Attendee[]    $attendees  Attendees of the calendar event to receive the notification.
     * @return Attendee[]
     */
    protected function getAttendeesWithoutRelatedUser(User $ownerUser, array $attendees)
    {
        $result = [];
        foreach ($attendees as $attendee) {
            if (!$attendee->isUserEqual($ownerUser)) {
                $result[] = $attendee;
            }
        }
        return $result;
    }

    /**
     * Sends all added notifications.
     */
    protected function sendAddedNotifications()
    {
        $this->emailNotificationSender->sendAddedNotifications();
    }

    /**
     * Adds invite notifications for attendees of calendar event except owner of the event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param User          $ownerUser      Owner user of main calendar event.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    protected function addInviteNotifications(
        CalendarEvent $calendarEvent,
        User $ownerUser,
        array $attendees
    ) {
        $filteredAttendees = $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees);
        if ($filteredAttendees) {
            $this->emailNotificationSender->addInviteNotifications(
                $calendarEvent,
                $filteredAttendees
            );
        }
    }

    /**
     * Handle calendar event notification on update of the event.
     *
     * @param CalendarEvent $calendarEvent          Actual calendar event.
     * @param CalendarEvent $originalCalendarEvent  Original calendar event.
     * @param string        $strategy               Could be one of next values: none, all, added_or_deleted.
     *
     * @see NotificationManager::ALL_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::NONE_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
     */
    public function onUpdate(CalendarEvent $calendarEvent, CalendarEvent $originalCalendarEvent, $strategy)
    {
        if ($strategy === static::NONE_NOTIFICATIONS_STRATEGY) {
            return;
        }

        if ($calendarEvent->getSystemCalendar()) {
            $this->onUpdateSystemCalendarEvent($originalCalendarEvent);
        } elseif ($calendarEvent->isCancelled() && !$originalCalendarEvent->isCancelled()) {
            $this->onCancelRecurringCalendarEventException($calendarEvent, $originalCalendarEvent);
        } elseif ($originalCalendarEvent->isCancelled() && !$calendarEvent->isCancelled()) {
            $this->onUnCancelRecurringCalendarEventException($calendarEvent);
        } elseif (!$calendarEvent->isCancelled()) {
            $this->onUpdateCalendarEvent($calendarEvent, $originalCalendarEvent, $strategy);
        }
    }

    /**
     * If the event calendar was "user" and then it was changed to "system"
     * the attendees of the event in "user" calendar should receive cancel notification,
     * because "system" calendar cannot have attendees.
     *
     * No other notifications are supported for system calendar event.
     *
     * @param CalendarEvent $originalCalendarEvent  Original calendar event.
     */
    protected function onUpdateSystemCalendarEvent(CalendarEvent $originalCalendarEvent)
    {
        if ($originalCalendarEvent->getCalendar()) {
            $this->addCancelNotifications(
                $originalCalendarEvent,
                $this->getCalendarOwnerUser($originalCalendarEvent),
                $originalCalendarEvent->getAttendees()->toArray()
            );
            $this->sendAddedNotifications();
        }
    }

    /**
     * The recurring calendar event exception was cancelled.
     *
     * Cancel notification should be send but only in case if the original event was not cancelled before
     * to not send cancel notification more then 1 time.
     *
     * @param CalendarEvent $calendarEvent          Actual calendar event.
     * @param CalendarEvent $originalCalendarEvent  Original calendar event.
     *
     * @throws \InvalidArgumentException If $calendarEvent is not exception of recurring event.
     */
    protected function onCancelRecurringCalendarEventException(
        CalendarEvent $calendarEvent,
        CalendarEvent $originalCalendarEvent
    ) {
        if (!$calendarEvent->getRecurringEvent()) {
            // Check the event for consistency, since this case is valid only for exceptions of recurring event.
            throw new \InvalidArgumentException(
                'Inconsistent state of calendar event: Cancelled event should have relation to recurring event.'
            );
        }

        $attendees = [];
        $calendarEventOwnerUser = null;
        if ($originalCalendarEvent->getParent()) {
            // If this event is not parent event, then only its' attendee should be notified about cancelled
            // event, since other attendees were not removed
            if ($originalCalendarEvent->getRelatedAttendee()) {
                $attendees = [$originalCalendarEvent->getRelatedAttendee()];
            }
            $calendarEventOwnerUser = $this->getCalendarOwnerUser($originalCalendarEvent->getParent());
        } else {
            // If this event is a parent event, then all attendees should be notified about cancelled event
            $attendees = $calendarEvent->getAttendees()->toArray();
            $calendarEventOwnerUser = $this->getCalendarOwnerUser($calendarEvent);
        }

        $this->addCancelNotifications(
            $calendarEvent,
            $calendarEventOwnerUser,
            $attendees
        );

        $this->sendAddedNotifications();
    }

    /**
     * The event was cancelled before and then was reactivated, the case is not possible in the UI
     * but possible if someone uses the API. Invite notification should be send.
     *
     * @param CalendarEvent $calendarEvent          Actual calendar event.
     *
     * @throws \InvalidArgumentException If $calendarEvent is not exception of recurring event.
     */
    protected function onUnCancelRecurringCalendarEventException(CalendarEvent $calendarEvent)
    {
        $this->addInviteNotifications(
            $calendarEvent,
            $this->getCalendarOwnerUser($calendarEvent),
            $calendarEvent->getAttendees()->toArray()
        );
        $this->sendAddedNotifications();
    }

    /**
     * Covers other cases of calendar event update.
     *
     * @param CalendarEvent $calendarEvent          Actual calendar event.
     * @param CalendarEvent $originalCalendarEvent  Original calendar event.
     * @param string        $strategy               Could be one of next values: none, all, added_or_deleted.
     *
     * @see NotificationManager::ALL_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::NONE_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
     */
    protected function onUpdateCalendarEvent(
        CalendarEvent $calendarEvent,
        CalendarEvent $originalCalendarEvent,
        $strategy
    ) {
        // If calendar event exceptions were cleared cancel notification should be sent.
        if (count($calendarEvent->getRecurringEventExceptions()->toArray()) == 0 &&
            count($originalCalendarEvent->getRecurringEventExceptions()->toArray()) != 0
        ) {
            $this->addCancelNotificationsForRecurringEventExceptions(
                $originalCalendarEvent,
                $this->getCalendarOwnerUser($calendarEvent)
            );
        }

        // Regular case for update of the event.
        if ($strategy === static::ALL_NOTIFICATIONS_STRATEGY) {
            // Add update notification for attendees which existed in the event originally and exist now.
            $existedAttendees = $this->getEqualAttendees(
                $originalCalendarEvent,
                $calendarEvent->getAttendees()->toArray()
            );
            $this->addUpdateNotifications(
                $calendarEvent,
                $this->getCalendarOwnerUser($calendarEvent),
                $existedAttendees
            );
        }

        // Add notification to new attendees
        $addedAttendees = $this->getNotEqualAttendees(
            $originalCalendarEvent,
            $calendarEvent->getAttendees()->toArray()
        );

        $this->addInviteNotifications(
            $calendarEvent,
            $this->getCalendarOwnerUser($calendarEvent),
            $addedAttendees
        );

        // Add notification to attendees removed from the event
        $removedAttendees = $this->getNotEqualAttendees(
            $calendarEvent,
            $originalCalendarEvent->getAttendees()->toArray()
        );

        $this->addUnInviteNotifications(
            $originalCalendarEvent,
            $this->getCalendarOwnerUser($calendarEvent),
            $removedAttendees
        );

        $this->sendAddedNotifications();
    }

    /**
     * Adds notifications for attendees that are present in exception of recurrent event, but absent in recurring event.
     */
    protected function addCancelNotificationsForRecurringEventExceptions(
        CalendarEvent $calendarEvent,
        User $ownerUser
    ) {
        foreach ($calendarEvent->getRecurringEventExceptions() as $exceptionCalendarEvent) {
            if ($exceptionCalendarEvent->isCancelled()) {
                continue;
            }

            $extraAttendees = $this->getNotEqualAttendees(
                $calendarEvent,
                $exceptionCalendarEvent->getAttendees()->toArray()
            );

            $this->addCancelNotifications(
                $exceptionCalendarEvent,
                $ownerUser,
                $extraAttendees
            );
        }
    }

    /**
     * Returns $attendees presented in $calendarEvent.
     *
     * @param CalendarEvent $calendarEvent
     * @param Attendee[] $attendees
     * @return Attendee[]
     */
    protected function getEqualAttendees(CalendarEvent $calendarEvent, array $attendees)
    {
        $result = [];
        foreach ($attendees as $attendee) {
            if ($calendarEvent->getEqualAttendee($attendee)) {
                $result[] = $attendee;
            }
        }
        return $result;
    }

    /**
     * Returns $attendees not presented in $calendarEvent.
     *
     * @param CalendarEvent $calendarEvent
     * @param Attendee[] $attendees
     * @return Attendee[]
     */
    protected function getNotEqualAttendees(CalendarEvent $calendarEvent, array $attendees)
    {
        $result = [];
        foreach ($attendees as $attendee) {
            if (!$calendarEvent->getEqualAttendee($attendee)) {
                $result[] = $attendee;
            }
        }
        return $result;
    }

    /**
     * Adds update notifications for attendees of calendar event except owner of the event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param User          $ownerUser      Owner user of main calendar event.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    protected function addUpdateNotifications(CalendarEvent $calendarEvent, User $ownerUser, array $attendees)
    {
        $filteredAttendees = $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees);
        if ($filteredAttendees) {
            $this->emailNotificationSender->addUpdateNotifications(
                $calendarEvent,
                $filteredAttendees
            );
        }
    }

    /**
     * Adds invite notifications for attendees of calendar event except owner of the event.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of the notification.
     * @param User          $ownerUser      Owner user of main calendar event.
     * @param Attendee[]    $attendees      Attendees of the calendar event to receive the notification.
     */
    protected function addUnInviteNotifications(CalendarEvent $calendarEvent, User $ownerUser, array $attendees)
    {
        $filteredAttendees = $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees);
        if ($filteredAttendees) {
            $this->emailNotificationSender->addUnInviteNotifications(
                $calendarEvent,
                $filteredAttendees
            );
        }
    }

    /**
     * Handle calendar event notification on update of status of attendee of the event.
     *
     * @param CalendarEvent $calendarEvent Actual calendar event of attendee who changed invitation status.
     * @param string        $strategy      Could be one of next values: none, all.
     *
     * @see NotificationManager::ALL_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::NONE_NOTIFICATIONS_STRATEGY
     */
    public function onChangeInvitationStatus(CalendarEvent $calendarEvent, $strategy)
    {
        if ($strategy === static::NONE_NOTIFICATIONS_STRATEGY || !$calendarEvent->getParent()) {
            // Method is applicable only for child events.
            return;
        }

        $attendee = $calendarEvent->getRelatedAttendee();
        if (!$attendee) {
            // Check the event for consistency, since this case is valid only for events with related attendees.
            throw new \InvalidArgumentException(
                'Inconsistent state of calendar event: Related attendee is not exist.'
            );
        }

        // Invitation status change notification should be send.
        $this->addInvitationStatusChangeNotifications(
            $calendarEvent,
            $this->getCalendarOwnerUser($calendarEvent->getParent()),
            $attendee->getStatusCode()
        );

        $this->sendAddedNotifications();
    }

    /**
     * Add notifications when invitation status of the event was changed.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of attendee for the notification.
     * @param User          $ownerUser      Owner user of main calendar event.
     * @param string        $statusCode     New code of invitation status.
     */
    protected function addInvitationStatusChangeNotifications(
        CalendarEvent $calendarEvent,
        User $ownerUser,
        $statusCode
    ) {
        $this->emailNotificationSender->addInvitationStatusChangeNotifications(
            $calendarEvent,
            $ownerUser,
            $statusCode
        );
    }

    /**
     * Handle calendar event notification on delete of the event.
     *
     * @param CalendarEvent $calendarEvent Actual calendar event which was deleted.
     * @param string        $strategy      Could be one of next values: none, all.
     *
     * @see NotificationManager::ALL_NOTIFICATIONS_STRATEGY
     * @see NotificationManager::NONE_NOTIFICATIONS_STRATEGY
     */
    public function onDelete(CalendarEvent $calendarEvent, $strategy)
    {
        if ($strategy === static::NONE_NOTIFICATIONS_STRATEGY) {
            return;
        }

        if ($calendarEvent->getParent()) {
            $ownerUser = $this->getCalendarOwnerUser($calendarEvent->getParent());
            // Add notification to owner of the event when attendee had removed the event from his own calendar.
            $this->addDeleteChildCalendarEventNotifications($calendarEvent, $ownerUser);
        } else {
            $ownerUser = $this->getCalendarOwnerUser($calendarEvent);
            // Cancel notification should be send to attendees.
            $this->addCancelNotifications(
                $calendarEvent,
                $ownerUser,
                $calendarEvent->getAttendees()->toArray()
            );

            $this->addCancelNotificationsForRecurringEventExceptions($calendarEvent, $ownerUser);
        }
        $this->sendAddedNotifications();
    }

    /**
     * Add notifications when attendee deleted his child event from own calendar.
     *
     * @param CalendarEvent $calendarEvent  Calendar event of attendee for the notification.
     * @param User          $ownerUser      Owner user of main calendar event.
     */
    protected function addDeleteChildCalendarEventNotifications(
        CalendarEvent $calendarEvent,
        User $ownerUser
    ) {
        $this->emailNotificationSender->addDeleteChildCalendarEventNotifications($calendarEvent, $ownerUser);
    }
}
