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
     * @param UpdateAttendeeManager $updateAttendeeManager
     * @param UpdateChildManager $updateChildManager,
     * @param UpdateExceptionManager $updateExceptionManager,
     */
    public function __construct(
        UpdateAttendeeManager $updateAttendeeManager,
        UpdateChildManager $updateChildManager,
        UpdateExceptionManager $updateExceptionManager
    ) {
        $this->updateAttendeeManager = $updateAttendeeManager;
        $this->updateChildManager = $updateChildManager;
        $this->updateExceptionManager = $updateExceptionManager;
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
        $this->updateChildManager->onEventUpdate($actualEvent, $organization);

        if ($allowUpdateExceptions) {
            $this->updateExceptionManager->onEventUpdate($actualEvent, $originalEvent);
        }
    }
}
