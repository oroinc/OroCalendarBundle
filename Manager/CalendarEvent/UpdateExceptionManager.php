<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * Responsible to actualize the state of recurring event exception state after main event was updated.
 *
 * When exception event is created it can have some attributes overwritten and some attributes with original values.
 * For example exception event can be created with different title, but all other attributes could have the same
 * value as original recurring event,
 *
 * When recurring event is updated its' exceptions should be also updated according to next rules
 *
 * 1) If recurrence pattern or start/end date was updated then all exceptions should be cleared.
 * 2) If attribute has overwritten value in the exception then its' value should remain the same.
 * 3) If attribute has the value in the exception same as in recurring event then its' value should be updated.
 */
class UpdateExceptionManager
{
    /**
     * @var AttendeeManager
     */
    protected $attendeeManager;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param AttendeeManager $attendeeManager
     */
    public function __construct(AttendeeManager $attendeeManager)
    {
        $this->attendeeManager = $attendeeManager;
    }

    /**
     * Actualize state of recurring event exceptions after recurring event was updated.
     * - Updates attributes of recurring event exceptions.
     * - Or remove recurring event exceptions if recurrence pattern was changed.
     *
     * @param CalendarEvent $actualEvent          Actual calendar event.
     * @param CalendarEvent $originalEvent  Original calendar event state before update.
     *
     */
    public function onEventUpdate(CalendarEvent $actualEvent, CalendarEvent $originalEvent)
    {
        if ($this->shouldClearExceptions($actualEvent, $originalEvent)) {
            $this->clearExceptions($actualEvent);
        } else {
            $this->updateExceptions($actualEvent, $originalEvent);
        }
    }

    /**
     * Checks if change of the recurrence or start/end date change should clear the recurring event exceptions.
     *
     * @param CalendarEvent $actualEvent
     * @param CalendarEvent $originalEvent
     * @return bool
     */
    protected function shouldClearExceptions(CalendarEvent $actualEvent, CalendarEvent $originalEvent)
    {
        $result = false;
        $recurrence = $actualEvent->getRecurrence();
        $originalRecurrence = $originalEvent->getRecurrence();

        if ($originalRecurrence && !$recurrence) {
            // Recurrence existed before and was removed, exceptions should be cleared.
            $result = true;
        } elseif ($recurrence && !$recurrence->isEqual($originalRecurrence)) {
            // Recurrence was changed
            $result = true;
        } elseif (!$this->isDateTimeValueEqual($actualEvent->getEnd(), $originalEvent->getEnd())) {
            // Duration of the event was changed
            $result = true;
        } elseif (!$this->isDateTimeValueEqual($actualEvent->getStart(), $originalEvent->getStart())) {
            // Start date of the event was changed
            $result = true;
        }

        return $result;
    }

    /**
     * @param \DateTime|null $source
     * @param \DateTime|null $target
     * @return bool
     */
    protected function isDateTimeValueEqual(\DateTime $source = null, \DateTime $target = null)
    {
        if ($source && $target) {
            return $source->format('U') == $target->format('U');
        }

        return !$source && !$target;
    }

    /**
     * Clears all exceptions of the event.
     *
     * @param CalendarEvent $event
     */
    protected function clearExceptions(CalendarEvent $event)
    {
        $event->getRecurringEventExceptions()->clear();

        if ($event->getParent()) {
            foreach ($event->getChildEvents() as $childEvent) {
                $this->clearExceptions($childEvent);
            }
        }
    }

    /**
     * Updates all exceptions related to actual event and its' child events.
     *
     * @param CalendarEvent $actualEvent The actual state of the event before update.
     * @param CalendarEvent $originalEvent The original state of the event before update.
     */
    protected function updateExceptions(CalendarEvent $actualEvent, CalendarEvent $originalEvent)
    {
        foreach ($actualEvent->getRecurringEventExceptions() as $exceptionEvent) {
            $this->updateException($actualEvent, $originalEvent, $exceptionEvent);
        }

        foreach ($actualEvent->getChildEvents() as $childEvent) {
            $this->updateExceptions($childEvent, $originalEvent);
        }
    }

    /**
     * Updates recurring event exception.
     *
     * Calculates what data in exception has to be updated and updates it.
     *
     * Exception attribute value will not be updated in one of the cases:
     * - the value of attribute was not changed
     * - exception has its' owne overwritten value of the attribute (like custom title or description)
     *
     * @param CalendarEvent $actualEvent The actual state of the event before update.
     * @param CalendarEvent $originalEvent The original state of the event before update.
     * @param CalendarEvent $exceptionEvent The exception event to update.
     */
    protected function updateException(
        CalendarEvent $actualEvent,
        CalendarEvent $originalEvent,
        CalendarEvent $exceptionEvent
    ) {
        $this->syncExceptionFields($actualEvent, $originalEvent, $exceptionEvent);
        $this->syncAttendees($actualEvent, $originalEvent, $exceptionEvent);
    }

    /**
     * @param CalendarEvent $actualEvent The actual state of the event before update.
     * @param CalendarEvent $originalEvent The original state of the event before update.
     * @param CalendarEvent $exceptionEvent The exception event to update.
     */
    protected function syncExceptionFields(
        CalendarEvent $actualEvent,
        CalendarEvent $originalEvent,
        CalendarEvent $exceptionEvent
    ) {
        $fields = $this->getExceptionFieldsToSync();

        foreach ($fields as $field) {
            $this->syncField($actualEvent, $originalEvent, $exceptionEvent, $field);
        }
    }

    /**
     * Gets the list of fields that should be updated on updating recurring event.
     * This list must contain only fields that can be compared with '==='. For other fields
     * additional methods/logic should be applied.
     *
     * @return array
     */
    public function getExceptionFieldsToSync()
    {
        return [
            'title',
            'description',
            'allDay',
            'backgroundColor'
        ];
    }

    /**
     * @param CalendarEvent $actualEvent The actual state of the event before update.
     * @param CalendarEvent $originalEvent The original state of the event before update.
     * @param CalendarEvent $exceptionEvent The exception event to update.
     * @param string $field
     */
    protected function syncField(
        CalendarEvent $actualEvent,
        CalendarEvent $originalEvent,
        CalendarEvent $exceptionEvent,
        $field
    ) {
        $propertyAccessor = $this->getPropertyAccessor();
        $originalValue = $propertyAccessor->getValue($originalEvent, $field);
        $exceptionValue = $propertyAccessor->getValue($exceptionEvent, $field);
        if ($originalValue === $exceptionValue) {
            $propertyAccessor->setValue(
                $exceptionEvent,
                $field,
                $propertyAccessor->getValue($actualEvent, $field)
            );
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }
        return $this->propertyAccessor;
    }

    /**
     * Sync attendees collection of exception event if this is necessary.
     *
     * Collection should be synced only in case if:
     * 1) Exception has no overridden state of the attendees list.
     * 2) The attendees list has been changed in the recurring event.
     *
     * @param CalendarEvent $actualEvent The actual state of the event before update.
     * @param CalendarEvent $originalEvent The original state of the event before update.
     * @param CalendarEvent $exceptionEvent The exception event to update.
     */
    protected function syncAttendees(
        CalendarEvent $actualEvent,
        CalendarEvent $originalEvent,
        CalendarEvent $exceptionEvent
    ) {
        if ($exceptionEvent->getParent()) {
            // Only child events should be updated
            return;
        }

        if (!$this->hasEqualAttendees($originalEvent, $exceptionEvent)) {
            // Exception has an overridden value of attendees list, it should not be synced now.
            return;
        }

        if (!$this->hasEqualAttendees($actualEvent, $originalEvent)) {
            // Attendees collection has been changed and now the change should be synced.
            foreach ($exceptionEvent->getAttendees() as $attendee) {
                $equalAttendee = $actualEvent->getEqualAttendee($attendee);
                if ($equalAttendee) {
                    // Update status of the attendee in the exception
                    $attendee->setStatus($equalAttendee->getStatus());
                } else {
                    // Remove attendee from the exception since it was removed in the actual event
                    $exceptionEvent->removeAttendee($attendee);
                }
            }

            foreach ($actualEvent->getAttendees() as $sourceAttendee) {
                if (!$exceptionEvent->getEqualAttendee($sourceAttendee)) {
                    // Add attendee to the exception event since it was added in the actual event
                    $exceptionEvent->addAttendee(
                        $this->attendeeManager->createAttendeeCopy($sourceAttendee)
                    );
                }
            }
        }
    }

    /**
     * Compares attendees of 2 events.
     *
     * @param CalendarEvent $source
     * @param CalendarEvent $target
     * @return bool
     */
    protected function hasEqualAttendees(CalendarEvent $source, CalendarEvent $target)
    {
        return $source->hasEqualAttendees($target);
    }
}
