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
     * @param CalendarEvent $calendarEvent
     */
    protected function updateExistingChildEvents(CalendarEvent $calendarEvent)
    {
        foreach ($calendarEvent->getChildEvents() as $childEvent) {
            $childEvent->setTitle($calendarEvent->getTitle())
                ->setDescription($calendarEvent->getDescription())
                ->setStart($calendarEvent->getStart())
                ->setEnd($calendarEvent->getEnd())
                ->setAllDay($calendarEvent->getAllDay());

            // If event is exception of recurring event
            if ($calendarEvent->getRecurringEvent() && $childEvent->getCalendar()) {
                // Get respective recurring event in child calendar
                $childRecurringEvent = $calendarEvent->getRecurringEvent()
                    ->getChildEventByCalendar($childEvent->getCalendar());

                // Associate child event with child recurring event
                $childEvent->setRecurringEvent($childRecurringEvent);

                // Sync original start
                $childEvent->setOriginalStart($calendarEvent->getOriginalStart());
            }
        }
    }

    /**
     * Creates missing child events of the main event.
     *
     * Every attendee of the event should have a event in respective calendar.
     *
     * @param CalendarEvent $calendarEvent
     * @param Organization $organization Current organization
     */
    protected function createMissingChildEvents(CalendarEvent $calendarEvent, Organization $organization)
    {
        $attendeeUsers = $this->getAttendeeUserIds($calendarEvent);
        $calendarUsers = $this->getCalendarUserIds($calendarEvent);

        $missingUsers = array_diff($attendeeUsers, $calendarUsers);
        $missingUsers = array_intersect($missingUsers, $attendeeUsers);

        if (!empty($missingUsers)) {
            $this->createChildEvents($calendarEvent, $missingUsers, $organization);
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
     * @param CalendarEvent $parent
     *
     * @param array $userOwnerIds
     * @param Organization $organization Current organization
     */
    protected function createChildEvents(CalendarEvent $parent, array $userOwnerIds, Organization $organization)
    {
        /** @var CalendarRepository $calendarRepository */
        $calendarRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');

        /** @var Calendar $calendar */
        $calendars = $calendarRepository->findDefaultCalendars($userOwnerIds, $organization->getId());

        foreach ($calendars as $calendar) {
            $childEvent = new CalendarEvent();
            $childEvent->setCalendar($calendar);
            $parent->addChildEvent($childEvent);

            $childEvent->setRelatedAttendee($childEvent->findRelatedAttendee());

            $this->copyRecurringEventExceptions($parent, $childEvent);
        }
    }

    /**
     * @param CalendarEvent $parentEvent
     * @param CalendarEvent $childEvent
     */
    protected function copyRecurringEventExceptions(CalendarEvent $parentEvent, CalendarEvent $childEvent)
    {
        if (!$parentEvent->getRecurrence()) {
            // If this is not recurring event then there are no exceptions to copy
            return;
        }

        foreach ($parentEvent->getRecurringEventExceptions() as $parentException) {
            $isPresent = false;

            foreach ($parentException->getChildEvents() as $existChildException) {
                if ($existChildException->getCalendar()->getId() == $childEvent->getCalendar()->getId()) {
                    $isPresent = true;
                    $existChildException->setRecurringEvent($childEvent);
                    break;
                }
            }

            if (!$isPresent) {
                $childException = new CalendarEvent();
                $childException->setCalendar($childEvent->getCalendar())
                    ->setTitle($parentException->getTitle())
                    ->setDescription($parentException->getDescription())
                    ->setStart($parentException->getStart())
                    ->setEnd($parentException->getEnd())
                    ->setOriginalStart($parentException->getOriginalStart())
                    ->setCancelled($parentException->isCancelled())
                    ->setAllDay($parentException->getAllDay())
                    ->setRecurringEvent($childEvent);

                $parentException->addChildEvent($childException);
            }
        }
    }
}
