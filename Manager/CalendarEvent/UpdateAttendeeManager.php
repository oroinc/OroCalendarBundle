<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Responsible to actualize attendees state after the event was created/updated:
 * - Bind attendees with users from $organization.
 * - Update related attendee of the event.
 * - Set default attendee status.
 * - Update attendees with empty display name.
 */
class UpdateAttendeeManager
{
    /**
     * @var AttendeeRelationManager
     */
    protected $attendeeRelationManager;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    public function __construct(AttendeeRelationManager $attendeeRelationManager, ManagerRegistry $doctrine)
    {
        $this->attendeeRelationManager = $attendeeRelationManager;
        $this->doctrine = $doctrine;
    }

    /**
     * Actualize attendees state after the event was created/updated
     */
    public function onEventUpdate(CalendarEvent $calendarEvent, Organization $organization)
    {
        $this->attendeeRelationManager->bindAttendees($calendarEvent->getAttendees(), $organization);

        $calendarEvent->setRelatedAttendee($calendarEvent->findRelatedAttendee());

        $this->setDefaultAttendeeStatus($calendarEvent->getAttendees());

        $this->setAttendeesType($calendarEvent);

        $this->updateAttendeeDisplayNames($calendarEvent->getAttendees());
    }

    /**
     * @param Collection|Attendee[] $attendees
     */
    protected function setDefaultAttendeeStatus($attendees)
    {
        foreach ($attendees as $attendee) {
            if (!$attendee || $attendee->getStatus()) {
                continue;
            }

            $statusEnum = $this->doctrine
                ->getRepository(EnumOption::class)
                ->find(
                    ExtendHelper::buildEnumOptionId(
                        Attendee::STATUS_ENUM_CODE,
                        Attendee::STATUS_NONE
                    )
                );

            $attendee->setStatus($statusEnum);
        }
    }

    protected function setAttendeesType(CalendarEvent $calendarEvent)
    {
        foreach ($calendarEvent->getAttendees() as $attendee) {
            if (!$attendee || $attendee->getType()) {
                continue;
            }

            if ($this->isAttendeeRelatedToCalendarEventOwnerUser($calendarEvent, $attendee)) {
                $ownerUserAttendeeType = $this->getAttendeeTypeForCalendarEventOwnerUser();
                $attendee->setType($ownerUserAttendeeType);
            } else {
                $defaultAttendeeType = $this->getAttendeeTypeByDefault();
                $attendee->setType($defaultAttendeeType);
            }
        }
    }

    /**
     * @param CalendarEvent $calendarEvent
     * @param Attendee $attendee
     * @return bool
     */
    protected function isAttendeeRelatedToCalendarEventOwnerUser(CalendarEvent $calendarEvent, Attendee $attendee)
    {
        return $calendarEvent->getCalendar() && $attendee->isUserEqual($calendarEvent->getCalendar()->getOwner());
    }

    /**
     * @return null|EnumOptionInterface
     */
    protected function getAttendeeTypeForCalendarEventOwnerUser()
    {
        return $this->getAttendeeType(Attendee::TYPE_ORGANIZER);
    }

    /**
     * @return null|EnumOptionInterface
     */
    protected function getAttendeeTypeByDefault()
    {
        return $this->getAttendeeType(Attendee::TYPE_REQUIRED);
    }

    /**
     * @return null|EnumOptionInterface
     */
    protected function getAttendeeType(string $internalId)
    {
        /** @var EnumOptionInterface $attendeeType */
        return $this->doctrine
            ->getRepository(EnumOption::class)
            ->find(ExtendHelper::buildEnumOptionId(Attendee::TYPE_ENUM_CODE, $internalId));
    }

    /**
     * Set displayName if it is empty.
     *
     * @param Collection|Attendee[] $attendees
     */
    protected function updateAttendeeDisplayNames($attendees)
    {
        foreach ($attendees as $attendee) {
            if (!$attendee->getDisplayName()) {
                $attendee->setDisplayName($attendee->getEmail());
            }
        }
    }
}
