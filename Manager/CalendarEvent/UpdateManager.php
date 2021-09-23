<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Responsible to actualize event state after it was updated.
 * - Actualize attendees state.
 * - Actualize child events state according to attendees.
 * - Actualize recurring calendar event exceptions state.
 */
class UpdateManager
{
    protected UpdateAttendeeManager $updateAttendeeManager;
    protected UpdateChildManager $updateChildManager;
    protected UpdateExceptionManager $updateExceptionManager;
    protected MatchingEventsManager $matchingEventsManager;
    protected FeatureChecker $featureChecker;

    public function __construct(
        UpdateAttendeeManager $updateAttendeeManager,
        UpdateChildManager $updateChildManager,
        UpdateExceptionManager $updateExceptionManager,
        MatchingEventsManager $matchingEventsManager,
        FeatureChecker $featureChecker
    ) {
        $this->updateAttendeeManager = $updateAttendeeManager;
        $this->updateChildManager = $updateChildManager;
        $this->updateExceptionManager = $updateExceptionManager;
        $this->matchingEventsManager = $matchingEventsManager;
        $this->featureChecker = $featureChecker;
    }

    /**
     * Actualize event state after it was updated.
     *
     * @param CalendarEvent $actualEvent    Actual calendar event.
     * @param CalendarEvent $originalEvent  Original calendar event state before update.
     * @param Organization $organization    Organization is used to match users to attendees by their email.
     * @param bool $allowUpdateExceptions   If TRUE then exceptions data should be updated
     */
    public function onEventUpdate(
        CalendarEvent $actualEvent,
        CalendarEvent $originalEvent,
        Organization $organization,
        $allowUpdateExceptions
    ) {
        $this->updateAttendeeManager->onEventUpdate($actualEvent, $organization);
        $this->matchingEventsManager->onEventUpdate($actualEvent);

        if ($this->featureChecker->isFeatureEnabled('calendar_events_attendee_duplications')) {
            $this->updateChildManager->onEventUpdate($actualEvent, $originalEvent, $organization);
        }

        if ($allowUpdateExceptions) {
            $this->updateExceptionManager->onEventUpdate($actualEvent, $originalEvent);
        }

        $this->setUpdatedAt($actualEvent, $originalEvent);
    }

    /**
     * When only recurrence or attendees collection was updated calendar event is not
     * added to UoW as updated entity and we need to force update of "updateAt" field to have
     * API clients know about the updated happened.
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
