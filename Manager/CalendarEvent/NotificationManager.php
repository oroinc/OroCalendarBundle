<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationSender;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Can decide to send notification in case when event is created, updated or deleted.
 *
 * Responsible for 2 things:
 * - identify what kind of notification should be send for the event.
 * - what attendees should be notified.
 *
 * Actual send of the notification is performed in the email notification sender.
 *
 * @see \Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationProcessor
 */
class NotificationManager
{
    /**
     * @var EmailNotificationSender
     */
    protected $emailNotificationSender;

    /**
     * EmailNotificationHandler constructor.
     *
     * @param EmailNotificationSender $processor
     */
    public function __construct(EmailNotificationSender $processor)
    {
        $this->emailNotificationSender = $processor;
    }

    /**
     * Handle calendar event notification on create of the event.
     *
     * @param CalendarEvent $calendarEvent Actual calendar event.
     * @throws \InvalidArgumentException When calendar event is cancelled and has no relation to recurring event.
     */
    public function onCreate(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->getSystemCalendar()) {
            // Notifications are not supported for events in system calendars
        } elseif ($calendarEvent->isCancelled()) {
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
     *
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
        $this->emailNotificationSender->addCancelNotifications(
            $calendarEvent,
            $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees)
        );
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
        $this->emailNotificationSender->addInviteNotifications(
            $calendarEvent,
            $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees)
        );
    }

    /**
     * Handle calendar event notification on update of the event.
     *
     * @param CalendarEvent $calendarEvent          Actual calendar event.
     * @param CalendarEvent $originalCalendarEvent  Original calendar event.
     * @param bool          $notifyExistedUsers     Used skip notification for existed users when the event was updated.
     *                                              Added or removed users will receive notification in any case.
     */
    public function onUpdate(CalendarEvent $calendarEvent, CalendarEvent $originalCalendarEvent, $notifyExistedUsers)
    {
        if ($calendarEvent->getSystemCalendar()) {
            // If the event calendar was "user" and then it was changed to "system"
            // the attendees of the event in "user" calendar should receive cancel notification,
            // because "system" calendar cannot have attendees.
            if ($originalCalendarEvent->getCalendar()) {
                $this->addCancelNotifications(
                    $originalCalendarEvent,
                    $this->getCalendarOwnerUser($originalCalendarEvent),
                    $originalCalendarEvent->getAttendees()->toArray()
                );
                $this->sendAddedNotifications();
            }
            // No other notifications are supported for system calendar event.
        } elseif ($calendarEvent->isCancelled() && !$originalCalendarEvent->isCancelled()) {
            // The recurring calendar event exception was cancelled.
            if (!$calendarEvent->getRecurringEvent()) {
                // Check the event for consistency, since this case is valid only for exceptions of recurring event.
                throw new \InvalidArgumentException(
                    'Inconsistent state of calendar event: Cancelled event should have relation to recurring event.'
                );
            }
            if (!$originalCalendarEvent->isCancelled()) {
                // Cancel notification should be send but only in case if the original event was not cancelled before
                // to not send cancel notification more then 1 time.
                $this->addCancelNotifications(
                    $calendarEvent,
                    $this->getCalendarOwnerUser($calendarEvent),
                    $calendarEvent->getAttendees()->toArray()
                );
                $this->sendAddedNotifications();
            }
            return;
        } elseif ($originalCalendarEvent->isCancelled() && !$calendarEvent->isCancelled()) {
            // The event was cancelled before and then was reactivated, the case is not possible in
            // the UI but possible if someone uses the API.
            // Invite notification should be send.
            $this->addInviteNotifications(
                $calendarEvent,
                $this->getCalendarOwnerUser($calendarEvent),
                $calendarEvent->getAttendees()->toArray()
            );
            $this->sendAddedNotifications();
        } elseif (!$calendarEvent->isCancelled()) {
            // Regular case for update of the event.

            if ($notifyExistedUsers) {
                // Add update notification for attendees which existed in the event originally and exist now.
                $existedAttendees = $this->getExistedAttendees(
                    $originalCalendarEvent->getAttendees(),
                    $calendarEvent->getAttendees()
                );
                $this->addUpdateNotifications(
                    $calendarEvent,
                    $this->getCalendarOwnerUser($calendarEvent),
                    $existedAttendees
                );

                //if calendar event exceptions were cleared,
                // cancel notification should be sent to extra attendees in these exceptions
                if (count($calendarEvent->getRecurringEventExceptions()->toArray()) == 0
                    && count($originalCalendarEvent->getRecurringEventExceptions()->toArray()) != 0
                ) {
                    $this->addDeleteEventExceptionWithExtraAttendeesNotification(
                        $originalCalendarEvent,
                        $this->getCalendarOwnerUser($calendarEvent)
                    );
                }
            }

            // Add notification to new attendees
            $addedAttendees = $this->getAddedAttendees(
                $originalCalendarEvent->getAttendees(),
                $calendarEvent->getAttendees()
            );

            $this->addInviteNotifications(
                $calendarEvent,
                $this->getCalendarOwnerUser($calendarEvent),
                $addedAttendees
            );

            // Add notification to attendees removed from the event
            $removedAttendees = $this->getRemovedAttendees(
                $originalCalendarEvent->getAttendees(),
                $calendarEvent->getAttendees()
            );

            $this->addUnInviteNotifications(
                $originalCalendarEvent,
                $this->getCalendarOwnerUser($calendarEvent),
                $removedAttendees
            );

            $this->sendAddedNotifications();
        }
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
        $this->emailNotificationSender->addUpdateNotifications(
            $calendarEvent,
            $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees)
        );
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
        $this->emailNotificationSender->addUnInviteNotifications(
            $calendarEvent,
            $this->getAttendeesWithoutRelatedUser($ownerUser, $attendees)
        );
    }

    /**
     * Returns array of attendees which were existed before in $originalAttendees and exist now in $actualAttendees.
     *
     * @param Collection $originalAttendees
     * @param Collection $actualAttendees
     * @return Attendee[]
     */
    protected function getExistedAttendees(Collection $originalAttendees, Collection $actualAttendees)
    {
        $result = [];
        foreach ($actualAttendees as $actualAttendee) {
            if ($originalAttendees->contains($actualAttendee)) {
                $result[] = $actualAttendee;
            }
        }
        return $result;
    }

    /**
     * Returns array of attendees which were not existed before in $originalAttendees and exist now in $actualAttendees.
     *
     * @param Collection $originalAttendees
     * @param Collection $actualAttendees
     * @return Attendee[]
     */
    protected function getAddedAttendees(Collection $originalAttendees, Collection $actualAttendees)
    {
        $result = [];
        foreach ($actualAttendees as $actualAttendee) {
            if (!$originalAttendees->contains($actualAttendee)) {
                $result[] = $actualAttendee;
            }
        }
        return $result;
    }

    /**
     * Returns array of attendees which were not existed before in $originalAttendees and exist now in $actualAttendees.
     *
     * @param Collection $originalAttendees
     * @param Collection $actualAttendees
     * @return Attendee[]
     */
    protected function getRemovedAttendees(Collection $originalAttendees, Collection $actualAttendees)
    {
        $result = [];
        foreach ($originalAttendees as $originalAttendee) {
            if (!$actualAttendees->contains($originalAttendee)) {
                $result[] = $originalAttendee;
            }
        }
        return $result;
    }

    /**
     * Handle calendar event notification on update of status of attendee of the event.
     *
     * @param CalendarEvent $calendarEvent Actual calendar event of attendee who changed invitation status.
     */
    public function onChangeInvitationStatus(CalendarEvent $calendarEvent)
    {
        if (!$calendarEvent->getParent()) {
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
        $this->emailNotificationSender->addInvitationStatusChangeNotifications(
            $calendarEvent,
            $this->getCalendarOwnerUser($calendarEvent->getParent()),
            $attendee->getStatusCode()
        );

        $this->sendAddedNotifications();
    }

    /**
     * Handle calendar event notification on delete of the event.
     *
     * @param CalendarEvent $calendarEvent Actual calendar event which was deleted.
     */
    public function onDelete(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->getParent()) {
            $ownerUser = $this->getCalendarOwnerUser($calendarEvent->getParent());
            // Add notification to owner of the event when attendee had removed the event from his own calendar.
            $this->emailNotificationSender->addDeleteChildCalendarEventNotifications($calendarEvent, $ownerUser);
        } else {
            $ownerUser = $this->getCalendarOwnerUser($calendarEvent);
            // Cancel notification should be send to attendees.
            $this->addCancelNotifications(
                $calendarEvent,
                $ownerUser,
                $calendarEvent->getAttendees()->toArray()
            );

            $this->addDeleteEventExceptionWithExtraAttendeesNotification($calendarEvent, $ownerUser);
        }
        $this->sendAddedNotifications();
    }

    /**
     * Adds notifications for attendees that are present in exception of recurrent event,
     * but absent in recurring event
     *
     * @param CalendarEvent $calendarEvent
     * @param User $ownerUser
     *
     * @return NotificationManager
     */
    protected function addDeleteEventExceptionWithExtraAttendeesNotification(
        CalendarEvent $calendarEvent,
        User $ownerUser
    ) {
        foreach ($calendarEvent->getRecurringEventExceptions() as $eventException) {
            $eventExceptionAttendees = $eventException->getAttendees();
            foreach ($eventExceptionAttendees as $eventExceptionAttendee) {
                $attendeeEmail = $eventExceptionAttendee->getEmail();
                $isCancelled = $eventException->isCancelled();
                if ($attendeeEmail && !$isCancelled && !$calendarEvent->getAttendeeByEmail($attendeeEmail)) {
                    $this->addCancelNotifications(
                        $eventException,
                        $ownerUser,
                        [$eventExceptionAttendee]
                    );
                }
            }
        }

        return $this;
    }
}
