<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Actualize child events state after parent event was updated
     *
     * @param CalendarEvent $calendarEvent Actual calendar event.
     * @param Organization  $organization Current organization
     */
    public function onEventUpdate(CalendarEvent $calendarEvent, Organization $organization)
    {
        $attendeeUserIds = $this->getAttendeeUserIds($calendarEvent);
        $calendarUserIds = $this->getCalendarUserIds($calendarEvent);

        $newAttendeeUserIds = array_diff($attendeeUserIds, $calendarUserIds);
        if ($newAttendeeUserIds) {
            $this->createCalendarEventsCopiesForNewAttendees($calendarEvent, $organization, $newAttendeeUserIds);
        }

        $removedAttendeeUserIds = [];
        $isExceptionalCalendarEvent = !is_null($calendarEvent->getRecurringEvent());
        if ($isExceptionalCalendarEvent) {
            $mainEventAttendeeIds = $this->getAttendeeUserIds($calendarEvent->getRecurringEvent());
            $removedAttendeeUserIds = array_diff($mainEventAttendeeIds, $attendeeUserIds);
        }
        if ($removedAttendeeUserIds) {
            $this->createCalendarEventCopiesForRemovedAttendees($calendarEvent, $organization, $removedAttendeeUserIds);
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

    /**
     * Get ids of users which have this event in their calendar.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return array
     */
    protected function getCalendarUserIds(CalendarEvent $calendarEvent)
    {
        $result = [];
        foreach ($calendarEvent->getChildEvents() as $childEvent) {
            $childEventCalendar = $childEvent->getCalendar();
            if ($childEventCalendar && $childEventCalendar->getOwner()) {
                $result[] = $childEventCalendar->getOwner()->getId();
            }
        }
        return $result;
    }

    /**
     * @param CalendarEvent $calendarEvent
     * @param Organization  $organization
     * @param array         $newAttendeeUserIds
     */
    protected function createCalendarEventsCopiesForNewAttendees(
        CalendarEvent $calendarEvent,
        Organization $organization,
        array $newAttendeeUserIds
    ) {
        $newAttendeesCalendars = $this->getUsersDefaultCalendars($newAttendeeUserIds, $organization);
        foreach ($newAttendeesCalendars as $calendar) {
            $this->createChildCalendarEvent($calendar, $calendarEvent);

            if ($calendarEvent->getRecurrence()) {
                /**
                 * Create Calendar Event Exceptions Copies for new Attendees or update existing one
                 */
                foreach ($calendarEvent->getRecurringEventExceptions() as $parentException) {
                    $childCalendarEventException = $parentException->getChildEventByCalendar($calendar);
                    if (!$childCalendarEventException) {
                        $childCalendarEventException = $this->createChildCalendarEvent($calendar, $parentException);
                    }
                    $this->updateAttendeeCalendarEvent($parentException, $childCalendarEventException);
                    $childCalendarEventException->setCancelled($parentException->isCancelled());
                }
            }
        }
    }

    /**
     * @param CalendarEvent $calendarEvent
     * @param Organization  $organization
     * @param array         $removedAttendeeUserIds
     */
    protected function createCalendarEventCopiesForRemovedAttendees(
        CalendarEvent $calendarEvent,
        Organization $organization,
        array $removedAttendeeUserIds
    ) {
        $removedAttendeesCalendars = $this->getUsersDefaultCalendars($removedAttendeeUserIds, $organization);
        foreach ($removedAttendeesCalendars as $calendar) {
            $childCalendarEvent = $calendarEvent->getChildEventByCalendar($calendar);
            if (!$childCalendarEvent) {
                $child = $this->createChildCalendarEvent($calendar, $calendarEvent);
                $child->setCancelled(true);
            }
        }
    }

    /**
     * @param Calendar $calendar
     * @param CalendarEvent $parent
     *
     * @return CalendarEvent
     */
    protected function createChildCalendarEvent(Calendar $calendar, CalendarEvent $parent)
    {
        $result = new CalendarEvent();
        $result->setCalendar($calendar);
        $result->setParent($parent);
        $parent->addChildEvent($result);
        $result->setRelatedAttendee($result->findRelatedAttendee());

        return $result;
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
        $calendarRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');

        /** @var Calendar $calendar */
        return $calendarRepository->findDefaultCalendars($userIds, $organization->getId());
    }

    /**
     * Sync Attendee Events state with main event state
     *
     * @param CalendarEvent $calendarEvent
     */
    protected function updateAttendeesCalendarEvents(CalendarEvent $calendarEvent)
    {
        foreach ($calendarEvent->getChildEvents() as $child) {
            $this->updateAttendeeCalendarEvent($calendarEvent, $child);
        }
    }

    /**
     * Sync Single Attendee Event state with main event state
     *
     * @param CalendarEvent $parent
     * @param CalendarEvent $child
     */
    protected function updateAttendeeCalendarEvent(CalendarEvent $parent, CalendarEvent $child)
    {
        $child->setTitle($parent->getTitle())
            ->setDescription($parent->getDescription())
            ->setStart($parent->getStart())
            ->setEnd($parent->getEnd())
            ->setAllDay($parent->getAllDay());

        if ($parent->getRecurringEvent()) {
            // This event is an exception of recurring event
            // Get recurring event from calendar of child event
            $childRecurringEvent = $parent->getRecurringEvent()->getChildEventByCalendar($child->getCalendar());

            $child->setRecurringEvent($childRecurringEvent)
                ->setOriginalStart($parent->getOriginalStart());
        }
    }
}
