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
     * @param Organization $organization Current organization
     */
    public function onEventUpdate(CalendarEvent $calendarEvent, Organization $organization)
    {
        $this->createMissingChildEvents($calendarEvent, $organization);
        $this->updateExistingChildEvents($calendarEvent);
    }

    /**
     * Creates missing child events of the main event.
     *
     * Every attendee of the event should have a event in respective calendar.
     *
     * @param CalendarEvent $parent
     * @param Organization $organization Current organization
     */
    protected function createMissingChildEvents(CalendarEvent $parent, Organization $organization)
    {
        $attendeeUsers = $this->getAttendeeUserIds($parent);
        $calendarUsers = $this->getCalendarUserIds($parent);

        $missingUsers = array_diff($attendeeUsers, $calendarUsers);
        $missingUsers = array_intersect($missingUsers, $attendeeUsers);

        if (!empty($missingUsers)) {
            $this->createChildEvents($parent, $missingUsers, $organization);
        } elseif (!$parent->getId() && $parent->getRecurringEvent()) {
            //if it new exception with empty attendees,
            //so the same exception should be added to all children of recurring event
            foreach ($parent->getRecurringEvent()->getChildEvents() as $childEvent) {
                $childException = $this->createChildCalendarEvent($childEvent->getCalendar(), $childEvent);
                $this->updateChildEvent($childEvent, $childException);
                $childException
                    ->setOriginalStart($parent->getOriginalStart())
                    ->setCancelled(true)
                    ->setRecurringEvent($childEvent);
            }
        }
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
        $result = [];

        if ($calendarEvent->getRecurringEvent() && $calendarEvent->isCancelled()) {
            // Attendees of cancelled exception are taken from recurring event.
            $attendees = $calendarEvent->getRecurringEvent()->getAttendees();
        } else {
            $attendees = $calendarEvent->getAttendees();
        }

        foreach ($attendees as $attendee) {
            if ($attendee->getUser()) {
                $result[] = $attendee->getUser()->getId();
            }
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

        $calendar = $calendarEvent->getCalendar();
        if ($calendar && $calendar->getOwner()) {
            $result[] = $calendar->getOwner()->getId();
        }

        foreach ($calendarEvent->getChildEvents() as $childEvent) {
            $childEventCalendar = $childEvent->getCalendar();
            if ($childEventCalendar && $childEventCalendar->getOwner()) {
                $result[] = $childEventCalendar->getOwner()->getId();
            }
        }
        return $result;
    }

    /**
     * Creates child events for $parent in calendars of users with ids in $targetCalendarOwnerIds
     *
     * @param CalendarEvent $parent
     *
     * @param array $targetCalendarOwnerIds
     * @param Organization $organization Current organization
     */
    protected function createChildEvents(
        CalendarEvent $parent,
        array $targetCalendarOwnerIds,
        Organization $organization
    ) {
        /** @var CalendarRepository $calendarRepository */
        $calendarRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');

        /** @var Calendar $calendar */
        $calendars = $calendarRepository->findDefaultCalendars($targetCalendarOwnerIds, $organization->getId());

        foreach ($calendars as $calendar) {
            $child = $this->createChildCalendarEvent($calendar, $parent);
            $this->copyRecurringEventExceptions($parent, $child);
        }
    }

    /**
     * @param Calendar $calendar
     * @param CalendarEvent $parent
     * @return CalendarEvent
     */
    protected function createChildCalendarEvent(Calendar $calendar, CalendarEvent $parent)
    {
        $result = $this->createCalendarEvent($calendar);
        $result->setParent($parent);
        $parent->addChildEvent($result);
        $result->setRelatedAttendee($result->findRelatedAttendee());

        return $result;
    }

    /**
     * @param Calendar $calendar
     * @return CalendarEvent
     */
    protected function createCalendarEvent(Calendar $calendar)
    {
        $result = new CalendarEvent();
        $result->setCalendar($calendar);

        return $result;
    }

    /**
     * Copy exceptions of parent recurring event to every child recurring event.
     *
     * If parent recurring event has exception the same exception should exist in the calendar of
     * guest user.
     *
     * @param CalendarEvent $parent
     * @param CalendarEvent $child
     */
    protected function copyRecurringEventExceptions(CalendarEvent $parent, CalendarEvent $child)
    {
        if (!$parent->getRecurrence()) {
            // If this is not recurring event then there are no exceptions to copy
            return;
        }

        foreach ($parent->getRecurringEventExceptions() as $parentException) {
            $childException = $parentException->getChildEventByCalendar($child->getCalendar());
            if ($childException) {
                $childException->setRecurringEvent($child);
            } else {
                $childException = $this->createChildCalendarEvent($child->getCalendar(), $parentException);
                $this->updateChildEvent($parentException, $childException);
                $childException->setCancelled($parentException->isCancelled());
            }
        }
    }

    /**
     * Update attributes of child events.
     *
     * @param CalendarEvent $parent
     */
    protected function updateExistingChildEvents(CalendarEvent $parent)
    {
        foreach ($parent->getChildEvents() as $child) {
            $this->updateChildEvent($parent, $child);
        }
    }

    /**
     * Updates attributes of child event according to current state of parent event.
     *
     * @param CalendarEvent $parent
     * @param CalendarEvent $child
     */
    protected function updateChildEvent(CalendarEvent $parent, CalendarEvent $child)
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
