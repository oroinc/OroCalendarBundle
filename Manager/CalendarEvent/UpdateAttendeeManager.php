<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
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
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param AttendeeRelationManager $attendeeRelationManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(AttendeeRelationManager $attendeeRelationManager, DoctrineHelper $doctrineHelper)
    {
        $this->attendeeRelationManager = $attendeeRelationManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Actualize attendees state after the event was created/updated
     *
     * @param CalendarEvent $calendarEvent
     * @param Organization $organization
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

            $statusEnum = $this->doctrineHelper
                ->getEntityRepository(ExtendHelper::buildEnumValueClassName(Attendee::STATUS_ENUM_CODE))
                ->find(Attendee::STATUS_NONE);

            $attendee->setStatus($statusEnum);
        }
    }

    /**
     * @param CalendarEvent $calendarEvent
     */
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
     * @return null|AbstractEnumValue
     */
    protected function getAttendeeTypeForCalendarEventOwnerUser()
    {
        return $this->getAttendeeType(Attendee::TYPE_ORGANIZER);
    }

    /**
     * @return null|AbstractEnumValue
     */
    protected function getAttendeeTypeByDefault()
    {
        return $this->getAttendeeType(Attendee::TYPE_REQUIRED);
    }

    /**
     * @param string $id
     *
     * @return null|AbstractEnumValue
     */
    protected function getAttendeeType($id)
    {
        /** @var AbstractEnumValue $attendeeType */
        $attendeeType = $this->doctrineHelper
            ->getEntityRepository(ExtendHelper::buildEnumValueClassName(Attendee::TYPE_ENUM_CODE))
            ->find($id);

        return $attendeeType;
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
