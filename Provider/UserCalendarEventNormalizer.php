<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Serializes user`s calendar event object into an array.
 */
class UserCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    protected ?PropertyAccessorInterface $propertyAccessor = null;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * Converts calendar event object to an array to be exposed in the API
     *
     * @param CalendarEvent $event The calendar event object
     * @param int $calendarId The target calendar id
     * @param array $extraFields List of extra fields to be added to the event
     *
     * @return array
     */
    public function getCalendarEvent(CalendarEvent $event, $calendarId = null, array $extraFields = [])
    {
        $item = $this->transformEntity($this->serializeCalendarEvent($event, $extraFields));

        if (!$calendarId) {
            $calendarId = $item['calendar'];
        }

        $result = [$item];
        $this->applyListData($result, $calendarId);

        return $result[0];
    }

    /**
     * Serialize calendar event object into an array to be expose in the API.
     *
     * @param CalendarEvent $event Source calendar event instance.
     * @param array $extraFields List of extra fields to add.
     *
     * @return array Serialized data of calendar event.
     */
    protected function serializeCalendarEvent(CalendarEvent $event, array $extraFields = [])
    {
        $item = [
            'id'                    => $event->getId(),
            'uid'                   => $event->getUid(),
            'title'                 => $event->getTitle(),
            'description'           => $event->getDescription(),
            'start'                 => $event->getStart(),
            'end'                   => $event->getEnd(),
            'allDay'                => $event->getAllDay(),
            'backgroundColor'       => $event->getBackgroundColor(),
            'createdAt'             => $event->getCreatedAt(),
            'updatedAt'             => $event->getUpdatedAt(),
            'invitationStatus'      => $event->getInvitationStatus(),
            'parentEventId'         => $event->getParent() ? $event->getParent()->getId() : null,
            'calendar'              => $event->getCalendar() ? $event->getCalendar()->getId() : null,
            'recurringEventId'      => $event->getRecurringEvent() ? $event->getRecurringEvent()->getId() : null,
            'originalStart'         => $event->getOriginalStart(),
            'isCancelled'           => $event->isCancelled(),
            'relatedAttendeeUserId' => $event->getRelatedAttendeeUserId(),
            'isOrganizer'           => $event->isOrganizer(),
            'organizerEmail'        => $event->getOrganizerEmail(),
            'organizerDisplayName'  => $event->getOrganizerDisplayName(),
            'organizerUserId'       => $event->getOrganizerUser() ? $event->getOrganizerUser()->getId() : null
        ];

        $this->applySerializedRecurrence($item, $event);
        $this->applySerializedAttendees($item, $event);
        $this->applySerializedExtraFields($item, $event, $extraFields);

        return $item;
    }

    /**
     * Adds recurrence to the serialized calendar event data.
     *
     * @param array $item Serialized calendar event data to update.
     * @param CalendarEvent $event Source calendar event instance.
     */
    protected function applySerializedRecurrence(array &$item, CalendarEvent $event)
    {
        if ($recurrence = $event->getRecurrence()) {
            $item['recurrence'] = $this->serializeRecurrence($event->getRecurrence());
        }
    }

    /**
     * Serialize recurrence of the calendar event.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     */
    protected function serializeRecurrence(Recurrence $recurrence)
    {
        return [
            'id' => $recurrence->getId(),
            'recurrenceType' => $recurrence->getRecurrenceType(),
            'interval' => $recurrence->getInterval(),
            'instance' => $recurrence->getInstance(),
            'dayOfWeek' => $recurrence->getDayOfWeek(),
            'dayOfMonth' => $recurrence->getDayOfMonth(),
            'monthOfYear' => $recurrence->getMonthOfYear(),
            'startTime' => $recurrence->getStartTime(),
            'endTime' => $recurrence->getEndTime(),
            'occurrences' => $recurrence->getOccurrences(),
            'timezone' => $recurrence->getTimeZone()
        ];
    }

    /**
     * Serialize attendees collection of calendar event.
     *
     * @param array $item Serialized calendar event data to update.
     * @param CalendarEvent $event Source calendar event instance.
     */
    protected function applySerializedAttendees(array &$item, CalendarEvent $event)
    {
        $item['attendees'] = [];

        foreach ($event->getAttendees() as $attendee) {
            $item['attendees'][] = $this->serializeAttendee($attendee);
        }
    }

    /**
     * Serialize attendee of calendar event.
     *
     * @param Attendee $attendee
     *
     * @return array
     */
    protected function serializeAttendee(Attendee $attendee)
    {
        return $this->transformEntity(
            [
                'displayName' => $attendee->getDisplayName(),
                'email'       => $attendee->getEmail(),
                'userId'      => $this->getObjectValue($attendee, 'user.id'),
                'createdAt'   => $attendee->getCreatedAt(),
                'updatedAt'   => $attendee->getUpdatedAt(),
                'status'      => $this->getObjectValue($attendee, 'status.id'),
                'type'        => $this->getObjectValue($attendee, 'type.id'),
            ]
        );
    }

    /**
     * Adds extra fields to the serialized calendar event data.
     *
     * @param array $item Serialized calendar event data to update.
     * @param CalendarEvent $event Source calendar event instance.
     * @param array $extraFields List of extra fields to add.
     */
    protected function applySerializedExtraFields(array &$item, CalendarEvent $event, array $extraFields = [])
    {
        foreach ($extraFields as $field) {
            $item[$field] = $this->getObjectValue($event, $field);
        }
    }

    /**
     * @param mixed $object
     * @param string $propertyPath
     *
     * @return mixed|null
     */
    protected function getObjectValue($object, $propertyPath)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        try {
            return $propertyAccessor->getValue($object, $propertyPath);
        } catch (InvalidPropertyPathException|NoSuchPropertyException|UnexpectedTypeException $e) {
            return null;
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyItemPermissionsData(array &$item)
    {
        $item['editable'] =
            ($item['calendar'] === $this->getCurrentCalendarId())
            && empty($item['parentEventId'])
            && $this->authorizationChecker->isGranted('oro_calendar_event_update')
            && $item['isOrganizer'] !== false;

        $item['removable'] =
            ($item['calendar'] === $this->getCurrentCalendarId())
            && $this->authorizationChecker->isGranted('oro_calendar_event_delete');

        $item['editableInvitationStatus'] = $this->canChangeInvitationStatus($item);
    }

    /**
     * Returns TRUE if user can change his invitation status for the event.
     * By default user can change invitation status only if event he is related attendee of the event.
     *
     * @param array $item Calendar event data array
     *
     * @return boolean
     */
    protected function canChangeInvitationStatus(array $item)
    {
        return $this->calendarEventManager->canChangeInvitationStatus($item, $this->tokenAccessor->getUser());
    }

    /**
     * {@inheritdoc}
     */
    protected function afterApplyItemData(array &$item)
    {
        parent::afterApplyItemData($item);
        // Remove temporary property to not expose it in the API.
        unset($item['relatedAttendeeUserId']);
    }
}
