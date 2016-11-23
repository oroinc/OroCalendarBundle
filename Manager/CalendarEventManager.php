<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Exception\CalendarEventRelatedAttendeeNotFoundException;
use Oro\Bundle\CalendarBundle\Exception\StatusNotFoundException;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Component\PropertyAccess\PropertyAccessor;

class CalendarEventManager
{
    /** @var AttendeeManager */
    protected $attendeeManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /**
     * @param AttendeeManager      $attendeeManager
     * @param DoctrineHelper       $doctrineHelper
     * @param SecurityFacade       $securityFacade
     * @param EntityNameResolver   $entityNameResolver
     * @param SystemCalendarConfig $calendarConfig
     */
    public function __construct(
        AttendeeManager $attendeeManager,
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        EntityNameResolver $entityNameResolver,
        SystemCalendarConfig $calendarConfig
    ) {
        $this->attendeeManager    = $attendeeManager;
        $this->doctrineHelper     = $doctrineHelper;
        $this->securityFacade     = $securityFacade;
        $this->entityNameResolver = $entityNameResolver;
        $this->calendarConfig     = $calendarConfig;
    }

    /**
     * Gets a list of system calendars for which it is granted to add events
     *
     * @return array of [id, name, public]
     */
    public function getSystemCalendars()
    {
        /** @var SystemCalendarRepository $repo */
        $repo      = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $calendars = $repo->getCalendarsQueryBuilder($this->securityFacade->getOrganizationId())
            ->select('sc.id, sc.name, sc.public')
            ->getQuery()
            ->getArrayResult();

        // @todo: check ACL here. will be done in BAP-6575

        return $calendars;
    }

    /**
     * @param CalendarEvent $event
     * @param string $newStatus
     *
     * @throws CalendarEventRelatedAttendeeNotFoundException
     * @throws StatusNotFoundException
     */
    public function changeStatus(CalendarEvent $event, $newStatus)
    {
        $relatedAttendee = $event->getRelatedAttendee();
        if (!$relatedAttendee) {
            throw new CalendarEventRelatedAttendeeNotFoundException();
        }

        $statusEnum = $this->doctrineHelper
            ->getEntityRepository(ExtendHelper::buildEnumValueClassName(Attendee::STATUS_ENUM_CODE))
            ->find($newStatus);

        if (!$statusEnum) {
            throw new StatusNotFoundException(sprintf('Status "%s" does not exists', $newStatus));
        }

        $relatedAttendee->setStatus($statusEnum);
    }

    /**
     * Gets a list of user's calendars for which it is granted to add events
     *
     * @return array of [id, name]
     */
    public function getUserCalendars()
    {
        /** @var CalendarRepository $repo */
        $repo      = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');
        $calendars = $repo->getUserCalendarsQueryBuilder(
            $this->securityFacade->getOrganizationId(),
            $this->securityFacade->getLoggedUserId()
        )
            ->select('c.id, c.name')
            ->getQuery()
            ->getArrayResult();
        foreach ($calendars as &$calendar) {
            if (empty($calendar['name'])) {
                $calendar['name'] = $this->entityNameResolver->getName($this->securityFacade->getLoggedUser());
            }
        }

        return $calendars;
    }

    /**
     * Links an event with a calendar by its alias and id
     *
     * @param CalendarEvent $event
     * @param string        $calendarAlias
     * @param int           $calendarId
     *
     * @throws \LogicException
     * @throws ForbiddenException
     */
    public function setCalendar(CalendarEvent $event, $calendarAlias, $calendarId)
    {
        if ($calendarAlias === Calendar::CALENDAR_ALIAS) {
            $calendar = $event->getCalendar();
            if (!$calendar || $calendar->getId() !== $calendarId) {
                $event->setCalendar($this->findCalendar($calendarId));
            }
        } elseif (in_array($calendarAlias, [SystemCalendar::CALENDAR_ALIAS, SystemCalendar::PUBLIC_CALENDAR_ALIAS])) {
            $systemCalendar = $this->findSystemCalendar($calendarId);
            //@TODO: Added permission verification
            if ($systemCalendar->isPublic() && !$this->calendarConfig->isPublicCalendarEnabled()) {
                throw new ForbiddenException('Public calendars are disabled.');
            }
            if (!$systemCalendar->isPublic() && !$this->calendarConfig->isSystemCalendarEnabled()) {
                throw new ForbiddenException('System calendars are disabled.');
            }
            $event->setSystemCalendar($systemCalendar);
        } else {
            throw new \LogicException(
                sprintf('Unexpected calendar alias: "%s". CalendarId: %d.', $calendarAlias, $calendarId)
            );
        }
    }

    /**
     * Gets UID of a calendar this event belongs to
     * The calendar UID is a string includes a calendar alias and id in the following format: {alias}_{id}
     *
     * @param string $calendarAlias
     * @param int    $calendarId
     *
     * @return string
     */
    public function getCalendarUid($calendarAlias, $calendarId)
    {
        return sprintf('%s_%d', $calendarAlias, $calendarId);
    }

    /**
     * Extracts calendar alias and id from a calendar UID
     *
     * @param string $calendarUid
     *
     * @return array [$calendarAlias, $calendarId]
     */
    public function parseCalendarUid($calendarUid)
    {
        $delim = strrpos($calendarUid, '_');

        return [
            substr($calendarUid, 0, $delim),
            (int)substr($calendarUid, $delim + 1)
        ];
    }

    /**
     * @param int $calendarId
     *
     * @return Calendar|null
     */
    protected function findCalendar($calendarId)
    {
        return $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar')
            ->find($calendarId);
    }

    /**
     * @param int $calendarId
     *
     * @return SystemCalendar|null
     */
    protected function findSystemCalendar($calendarId)
    {
        return $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar')
            ->find($calendarId);
    }

    /**
     * @param Recurrence $recurrence
     */
    public function removeRecurrence(Recurrence $recurrence)
    {
        $this->doctrineHelper->getEntityManager($recurrence)->remove($recurrence);
    }

    /**
     * Responsible to actualize event state after it was updated.
     * - Delegate attendees state update to AttendeeManager.
     * - Update child events according to actualized state of attendees.
     *
     * @param CalendarEvent $event          Actual calendar event.
     * @param CalendarEvent $originalEvent  Original calendar event state before update.
     * @param Organization $organization    Organization is used to match users to attendees by their email.
     * @param bool $allowClearExceptions    If TRUE and current Recurrence state is different from original,
     *                                      then
     *
     */
    public function onEventUpdate(
        CalendarEvent $event,
        CalendarEvent $originalEvent,
        Organization $organization,
        $allowClearExceptions
    ) {
        $this->attendeeManager->onEventUpdate(
            $event,
            $organization
        );

        $this->updateChildEvents($event);

        if ($allowClearExceptions &&
            $this->shouldClearExceptions($event->getRecurrence(), $originalEvent->getRecurrence())
        ) {
            $this->clearExceptions($event);
        }
    }

    /**
     * Child events is updated for next reasons:
     *
     * - event has changes in field and child event should be synced
     * - new attendees added to the event - as a result new child event should correspond to user of the attendee.
     *
     * @param CalendarEvent $calendarEvent
     */
    protected function updateChildEvents(CalendarEvent $calendarEvent)
    {
        $this->createMissingChildEvents($calendarEvent);
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
     */
    protected function createMissingChildEvents(CalendarEvent $calendarEvent)
    {
        $attendeeUsers = $this->getAttendeeUserIds($calendarEvent);
        $calendarUsers = $this->getCalendarUserIds($calendarEvent);

        $missingUsers = array_diff($attendeeUsers, $calendarUsers);
        $missingUsers = array_intersect($missingUsers, $attendeeUsers);

        if (!empty($missingUsers)) {
            $this->createChildEvents($calendarEvent, $missingUsers);
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
     */
    protected function createChildEvents(CalendarEvent $parent, array $userOwnerIds)
    {
        /** @var CalendarRepository $calendarRepository */
        $calendarRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');
        $organizationId     = $this->securityFacade->getOrganizationId();

        $calendars = $calendarRepository->findDefaultCalendars($userOwnerIds, $organizationId);

        /** @var Calendar $calendar */
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
            // if this is not recurring event then there are no exceptions to copy
            return;
        }

        foreach ($parentEvent->getRecurringEventExceptions() as $parentException) {
            // $exception will be parent for new exception of attendee
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

    /**
     * Checks if recurrence change should clear exceptions
     *
     * @param Recurrence|null $recurrence
     * @param Recurrence|null $originalRecurrence
     * @return bool
     */
    protected function shouldClearExceptions(Recurrence $recurrence = null, Recurrence $originalRecurrence = null)
    {
        $result = false;

        if ($originalRecurrence && !$recurrence) {
            // Recurrence existed before and was removed, exceptions should be cleared.
            $result = true;
        } elseif ($recurrence && !$recurrence->isEqual($originalRecurrence)) {
            // Recurrence was changed
            $result = true;
        }

        return $result;
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
}
