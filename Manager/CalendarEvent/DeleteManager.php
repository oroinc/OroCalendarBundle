<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

/**
 * Responsible to delete calendar event with additional logic:
 * - Exception of recurring event could be cancelled instead of deleted.
 * - Child events for this case will be cancelled instead of deleted as well.
 * - Relations not supported cascade remove will be removed manually.
 */
class DeleteManager
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
     * This method is responsible to decide and delete or cancel calendar event.
     *
     * Note: Exception of calendar event could be cancelled instead of being deleted.
     *
     * @param CalendarEvent $calendarEvent Calendar event which should be deleted.
     * @param boolean $allowCancel If TRUE and event is an exception of recurring event it will be cancelled instead.
     */
    public function deleteOrCancel(CalendarEvent $calendarEvent, $allowCancel)
    {
        if ($allowCancel && $calendarEvent->getRecurringEvent()) {
            $this->doCancel($calendarEvent);
        } else {
            $this->doDelete($calendarEvent);
        }
    }

    /**
     * If this is an exception of recurring event, cancel instead of remove.
     */
    protected function doCancel(CalendarEvent $calendarEvent)
    {
        $calendarEvent->setCancelled(true);

        $parentCalendarEvent = $calendarEvent->getParent();

        if ($parentCalendarEvent) {
            // If this is a child event, cancel only this event and remove attendee from parent event.
            $attendee = $calendarEvent->getRelatedAttendee();
            if ($attendee) {
                $calendarEvent->setRelatedAttendee(null);
                $parentCalendarEvent->removeAttendee($attendee);
            }
        } else {
            // If this is a parent event, cancel event for all attendees.
            /** @var CalendarEvent $calendarEvent */
            foreach ($calendarEvent->getChildEvents() as $childCalendarEvent) {
                $childCalendarEvent->setCancelled(true);
            }
        }
    }

    /**
     * Delete calendar event from the persistence.
     */
    protected function doDelete(CalendarEvent $calendarEvent)
    {
        $manager = $this->doctrine->getManager();
        $manager->remove($calendarEvent);

        $this->deleteAndClearRecurringEventExceptions($calendarEvent);
    }

    /**
     * Recurring event exceptions intentionally doesn't have cascade remove, so this method can be used
     * when collection of recurring event exceptions should be cleared and removed.
     */
    public function deleteAndClearRecurringEventExceptions(CalendarEvent $event)
    {
        foreach ($event->getRecurringEventExceptions() as $exception) {
            $this->doctrine->getManager()->remove($exception);
        }
        $event->getRecurringEventExceptions()->clear();

        foreach ($event->getChildEvents() as $childEvent) {
            $this->deleteAndClearRecurringEventExceptions($childEvent);
        }
    }
}
