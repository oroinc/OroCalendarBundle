<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Responsible to actualize child event state after parent event was updated:
 * - Update child events according to changes in attribute values of parent event.
 * - Update child events according to new state of attendees.
 *
 * Child events is updated in next cases:
 * - event has changes in field and child event should be synced
 * - new attendees added to the event - as a result new child event should correspond to user of the attendee.
 */
class UpdateChildManager
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Actualize child events state after parent event was updated
     *
     * @param CalendarEvent $calendarEvent Actual calendar event.
     * @param CalendarEvent $originalEvent Actual calendar event.
     * @param Organization  $organization Current organization.
     */
    public function onEventUpdate(
        CalendarEvent $calendarEvent,
        CalendarEvent $originalEvent,
        Organization $organization
    ) {
        if ($calendarEvent->isOrganizer() === false && $calendarEvent->getParent() === null) {
            return;
        }

        $attendeeUserIds = $this->getAttendeeUserIds($calendarEvent);
        $calendarUserIds = $this->getAttendeeUserIds($originalEvent);

        $newAttendeeUserIds = array_diff($attendeeUserIds, $calendarUserIds);
        $this->createCalendarEventsCopiesForNewAttendees(
            $calendarEvent,
            $originalEvent,
            $organization,
            $newAttendeeUserIds
        );

        $removedAttendeeUserIds = array_diff($calendarUserIds, $attendeeUserIds);
        $this->removeOrCancelAttendeesCalendarEvents(
            $calendarEvent,
            $organization,
            $removedAttendeeUserIds
        );

        $isExceptionalCalendarEvent = !is_null($calendarEvent->getRecurringEvent());
        if ($isExceptionalCalendarEvent) {
            $mainEventAttendeeIds = $this->getAttendeeUserIds($calendarEvent->getRecurringEvent());
            $removedAttendeeUserIds = array_diff($mainEventAttendeeIds, $attendeeUserIds);
            if ($removedAttendeeUserIds) {
                $this->createCalendarEventCopiesForRemovedAttendees(
                    $calendarEvent,
                    $organization,
                    $removedAttendeeUserIds
                );
            }
        }

        $this->updateAttendeesCalendarEvents($calendarEvent);
    }

    /**
     * Get ids of users which related to attendees of this event.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return array
     */
    protected function getAttendeeUserIds(CalendarEvent $calendarEvent)
    {
        $calendar = $calendarEvent->getCalendar();
        $calendarEventOwnerId = $calendar && $calendar->getOwner()
            ? $calendar->getOwner()->getId()
            : null;

        $result = [];

        $attendees = $calendarEvent->getAttendees();
        foreach ($attendees as $attendee) {
            if (!$attendee->getUser()) {
                continue;
            }

            $userId = $attendee->getUser()->getId();
            if ($calendarEventOwnerId && $calendarEventOwnerId === $userId) {
                continue;
            }

            $result[] = $userId;
        }

        return $result;
    }

    protected function createCalendarEventsCopiesForNewAttendees(
        CalendarEvent $calendarEvent,
        CalendarEvent $originalEvent,
        Organization $organization,
        array $newAttendeeUserIds
    ) {
        $newAttendeesCalendars = $this->getUsersDefaultCalendars($newAttendeeUserIds, $organization);
        foreach ($newAttendeesCalendars as $attendeeCalendar) {
            $attendeeCalendarEvent = $calendarEvent->getChildEventByCalendar($attendeeCalendar);
            if (!$attendeeCalendarEvent) {
                $attendeeCalendarEvent = $this->createAttendeeCalendarEvent($attendeeCalendar, $calendarEvent);
            }
            $attendeeCalendarEvent->setCancelled($calendarEvent->isCancelled());

            if ($calendarEvent->getRecurrence()) {
                foreach ($calendarEvent->getRecurringEventExceptions() as $recurringEventException) {
                    $attendeeCalendarEventException = $recurringEventException
                        ->getChildEventByCalendar($attendeeCalendar);

                    if ($attendeeCalendarEventException) {
                        /**
                         * If Exceptional Calendar Event for Attendee is already exist it should be linked with
                         * new Recurring Calendar Event
                         */
                        $attendeeCalendarEventException->setRecurringEvent($attendeeCalendarEvent);
                    } else {
                        $attendeeCalendarEventException = $this->createAttendeeCalendarEvent(
                            $attendeeCalendar,
                            $recurringEventException
                        );
                    }

                    $attendeeCalendarEventException->setCancelled($recurringEventException->isCancelled());

                    /**
                     * Exception calendar event for attendee should be canceled if all next conditions apply:
                     * - Attendees of the exception has overridden value, different from attendees in recurring event.
                     * - Related attendee of the exception event is not in the list of attendees in recurring event.
                     */
                    $isOverriddenAttendees = !$originalEvent->hasEqualAttendees($recurringEventException);
                    if ($isOverriddenAttendees) {
                        $recurringEventHasAttendee = (bool)$recurringEventException
                            ->getAttendeeByCalendar($attendeeCalendar);
                        if (!$recurringEventHasAttendee) {
                            $attendeeCalendarEventException->setCancelled(true);
                        }
                    }
                }
            }
        }
    }

    /**
     * Removes calendar events of related to attendees which were removed from the event.
     *
     * For recurring calendar event exceptions instead of remove mark attendee event as cancelled.
     *
     * For recurring calendar event exceptions unlink reference with removed child calendar event.
     *
     * @param CalendarEvent $calendarEvent
     * @param Organization $organization
     * @param array $removedAttendeeUserIds
     */
    protected function removeOrCancelAttendeesCalendarEvents(
        CalendarEvent $calendarEvent,
        Organization $organization,
        $removedAttendeeUserIds
    ) {
        $removedAttendeesCalendars = $this->getUsersDefaultCalendars($removedAttendeeUserIds, $organization);
        foreach ($removedAttendeesCalendars as $removedAttendeeCalendar) {
            $childEvent = $calendarEvent->getChildEventByCalendar($removedAttendeeCalendar);
            if ($childEvent) {
                if ($childEvent->getRecurringEvent()) {
                    // If child event is an exception of recurring event then it should be cancelled
                    // to hide the event in user's calendar
                    $childEvent->setCancelled(true);
                } else {
                    // Otherwise it should be removed
                    $calendarEvent->removeChildEvent($childEvent);
                    // All references to this event from recurring event exceptions should be cleared
                    // since $childEvent will be removed.
                    foreach ($childEvent->getRecurringEventExceptions() as $recurringEventException) {
                        // After removing the reference to $childEvent from the $recurringEventException
                        // this event should become a regular child event representing event of extra attendee
                        // which was not added in recurring event but added in exception.
                        $recurringEventException->setRecurringEvent(null);
                    }
                }
            }
        }
    }

    protected function createCalendarEventCopiesForRemovedAttendees(
        CalendarEvent $calendarEvent,
        Organization $organization,
        array $removedAttendeeUserIds
    ) {
        $removedAttendeesCalendars = $this->getUsersDefaultCalendars($removedAttendeeUserIds, $organization);
        foreach ($removedAttendeesCalendars as $calendar) {
            $attendeeCalendarEvent = $calendarEvent->getChildEventByCalendar($calendar);
            if (!$attendeeCalendarEvent) {
                $attendeeCalendarEvent = $this->createAttendeeCalendarEvent($calendar, $calendarEvent);
                $attendeeCalendarEvent->setCancelled(true);
            }
        }
    }

    /**
     * @param Calendar      $calendar
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    protected function createAttendeeCalendarEvent(Calendar $calendar, CalendarEvent $calendarEvent)
    {
        $attendeeCalendarEvent = new CalendarEvent();
        $attendeeCalendarEvent->setCalendar($calendar);
        $attendeeCalendarEvent->setParent($calendarEvent);
        $attendeeCalendarEvent->setIsOrganizer(false);
        $calendarEvent->addChildEvent($attendeeCalendarEvent);
        $attendeeCalendarEvent->setRelatedAttendee($attendeeCalendarEvent->findRelatedAttendee());

        $this->copyOrganizerFields($calendarEvent, $attendeeCalendarEvent);
        $this->updateAttendeeCalendarEvent($calendarEvent, $attendeeCalendarEvent);

        return $attendeeCalendarEvent;
    }

    /**
     * @param int[]        $userIds
     * @param Organization $organization
     *
     * @return Calendar[]
     */
    protected function getUsersDefaultCalendars(array $userIds, Organization $organization)
    {
        /** @var CalendarRepository $calendarRepository */
        $calendarRepository = $this->doctrine->getRepository('OroCalendarBundle:Calendar');

        /** @var Calendar $calendar */
        return $calendarRepository->findDefaultCalendars($userIds, $organization->getId());
    }

    /**
     * Sync Attendee Events state with main event state
     */
    protected function updateAttendeesCalendarEvents(CalendarEvent $calendarEvent)
    {
        foreach ($calendarEvent->getChildEvents() as $attendeeCalendarEvent) {
            $this->updateAttendeeCalendarEvent($calendarEvent, $attendeeCalendarEvent);
        }
    }

    /**
     * Sync Single Attendee Event state with main event state
     */
    protected function updateAttendeeCalendarEvent(CalendarEvent $parent, CalendarEvent $child)
    {
        $child->setTitle($parent->getTitle())
            ->setDescription($parent->getDescription())
            ->setStart($parent->getStart())
            ->setEnd($parent->getEnd())
            ->setAllDay($parent->getAllDay());

        if ($parent->isCancelled()) {
            $child->setCancelled(true);
        }

        if ($parent->getRecurringEvent()) {
            // This event is an exception of recurring event
            // Get recurring event from calendar of child event
            $childRecurringEvent = $parent->getRecurringEvent()->getChildEventByCalendar($child->getCalendar());

            $child->setRecurringEvent($childRecurringEvent)
                ->setOriginalStart($parent->getOriginalStart());
        }
    }

    private function copyOrganizerFields(CalendarEvent $calendarEvent, CalendarEvent $attendeeCalendarEvent)
    {
        $this->copyFieldIfNotNull($calendarEvent, 'getOrganizerEmail', $attendeeCalendarEvent, 'setOrganizerEmail');
        $this->copyFieldIfNotNull(
            $calendarEvent,
            'getOrganizerDisplayName',
            $attendeeCalendarEvent,
            'setOrganizerDisplayName'
        );
        $this->copyFieldIfNotNull($calendarEvent, 'getOrganizerUser', $attendeeCalendarEvent, 'setOrganizerUser');
    }

    private function copyFieldIfNotNull(CalendarEvent $from, string $getter, CalendarEvent $to, string $setter)
    {
        if (!is_callable([$from, $getter]) || !is_callable([$to, $setter])) {
            return;
        }

        $value = $from->$getter();
        if ($from->$getter() !== null) {
            $to->$setter($value);
        }
    }
}
