<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Responsible to actualize event state after it was updated.
 * - Actualize attendees state.
 * - Actualize child events state according to attendees.
 * - Actualize recurring calendar event exceptions state.
 */
class UpdateManager
{
    /**
     * @var UpdateAttendeeManager
     */
    protected $updateAttendeeManager;

    /**
     * @var UpdateChildManager
     */
    protected $updateChildManager;

    /**
     * @var MatchingEventsManager
     */
    protected $matchingEventsManager;

    /**
     * @param UpdateAttendeeManager $updateAttendeeManager
     * @param UpdateChildManager $updateChildManager
     * @param UpdateExceptionManager $updateExceptionManager
     * @param MatchingEventsManager $matchingEventsManager
     */
    public function __construct(
        UpdateAttendeeManager $updateAttendeeManager,
        UpdateChildManager $updateChildManager,
        UpdateExceptionManager $updateExceptionManager,
        MatchingEventsManager $matchingEventsManager
    ) {
        $this->updateAttendeeManager = $updateAttendeeManager;
        $this->updateChildManager = $updateChildManager;
        $this->updateExceptionManager = $updateExceptionManager;
        $this->matchingEventsManager = $matchingEventsManager;
    }

    /**
     * Actualize event state after it was updated.
     *
     * @param CalendarEvent $actualEvent    Actual calendar event.
     * @param CalendarEvent $originalEvent  Original calendar event state before update.
     * @param Organization $organization    Organization is used to match users to attendees by their email.
     * @param bool $allowUpdateExceptions   If TRUE then exceptions data should be updated
     *
     */
    public function onEventUpdate(
        CalendarEvent $actualEvent,
        CalendarEvent $originalEvent,
        Organization $organization,
        $allowUpdateExceptions
    ) {
        $this->updateAttendeeManager->onEventUpdate($actualEvent, $organization);
        $this->matchingEventsManager->onEventUpdate($actualEvent);
        $this->updateChildManager->onEventUpdate($actualEvent, $originalEvent, $organization);

        if ($allowUpdateExceptions) {
            $this->updateExceptionManager->onEventUpdate($actualEvent, $originalEvent);
        }

        $this->setUpdatedAt($actualEvent, $originalEvent);
    }

    /**
     * When only recurrence or attendees collection was updated calendar event is not
     * added to UoW as updated entity and we need to force update of "updateAt" field to have
     * API clients know about the updated happened.
     *
     * @param CalendarEvent $calendarEvent
     * @param CalendarEvent $originalEvent
     */
    protected function setUpdatedAt(CalendarEvent $calendarEvent, CalendarEvent $originalEvent)
    {
        $hasUpdatedAttendees = !$calendarEvent->hasEqualAttendees($originalEvent);
        $hasUpdatedRecurrence = $calendarEvent->getRecurrence()
            && !$calendarEvent->getRecurrence()->isEqual($originalEvent->getRecurrence());

        if ($hasUpdatedAttendees || $hasUpdatedRecurrence) {
            $calendarEvent->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }
}
