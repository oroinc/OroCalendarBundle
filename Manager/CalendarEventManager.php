<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Exception\ChangeInvitationStatusException;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The class that helps to manage calendar events.
 */
class CalendarEventManager
{
    /** @var UpdateManager */
    protected $updateManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    public function __construct(
        UpdateManager $updateManager,
        ManagerRegistry $doctrine,
        TokenAccessorInterface $tokenAccessor,
        EntityNameResolver $entityNameResolver,
        SystemCalendarConfig $calendarConfig
    ) {
        $this->updateManager = $updateManager;
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
        $this->entityNameResolver = $entityNameResolver;
        $this->calendarConfig = $calendarConfig;
    }

    /**
     * Gets a list of system calendars for which it is granted to add events
     *
     * @return array of [id, name, public]
     */
    public function getSystemCalendars()
    {
        /** @var SystemCalendarRepository $repo */
        $repo      = $this->doctrine->getRepository('OroCalendarBundle:SystemCalendar');
        $calendars = $repo->getCalendarsQueryBuilder($this->tokenAccessor->getOrganizationId())
            ->select('sc.id, sc.name, sc.public')
            ->getQuery()
            ->getArrayResult();

        // check ACL here. will be done in BAP-6575

        return $calendars;
    }

    /**
     * Returns TRUE if current user can change status of the event.
     *
     * @param CalendarEvent|array $event Calendar event object or serialized data
     * @param User $user Target user
     * @return bool
     */
    public function canChangeInvitationStatus($event, User $user)
    {
        if ($event instanceof CalendarEvent) {
            $result = $event->isRelatedAttendeeUserEqual($user);
        } elseif (is_array($event)) {
            $relatedAttendeeUserId = isset($event['relatedAttendeeUserId']) ? $event['relatedAttendeeUserId'] : null;
            $result = (int)$user->getId() == (int)$relatedAttendeeUserId;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Event expected to be an array or instance of %s, but %s is given',
                    CalendarEvent::class,
                    is_object($event) ? get_class($event) : gettype($event)
                )
            );
        }

        return $result;
    }

    /**
     * Change status of calendar event for user.
     *
     * @param CalendarEvent $event Target event.
     * @param string $newStatus New invitation status.
     * @param User $user Target user.
     *
     * @throws ChangeInvitationStatusException
     */
    public function changeInvitationStatus(CalendarEvent $event, $newStatus, User $user)
    {
        $relatedAttendee = $event->getRelatedAttendee();

        if (!$relatedAttendee) {
            throw ChangeInvitationStatusException::changeInvitationStatusFailedWhenRelatedAttendeeNotExist();
        }

        $statusEnum = $this->doctrine
            ->getRepository(ExtendHelper::buildEnumValueClassName(Attendee::STATUS_ENUM_CODE))
            ->find($newStatus);

        if (!$statusEnum) {
            throw ChangeInvitationStatusException::invitationStatusNotFound($newStatus);
        }

        if (!$relatedAttendee->isUserEqual($user)) {
            throw ChangeInvitationStatusException::changeInvitationFailed();
        }

        if (!$this->canChangeInvitationStatus($event, $user)) {
            throw ChangeInvitationStatusException::changeInvitationFailed();
        }

        $relatedAttendee->setStatus($statusEnum);
        //need to update calendar event entity, so its view on frontend will be updated
        $event->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * Gets a list of user's calendars for which it is granted to add events
     *
     * @return array of [id, name]
     */
    public function getUserCalendars()
    {
        /** @var CalendarRepository $repo */
        $repo      = $this->doctrine->getRepository('OroCalendarBundle:Calendar');
        $calendars = $repo->getUserCalendarsQueryBuilder(
            $this->tokenAccessor->getOrganizationId(),
            $this->tokenAccessor->getUserId()
        )
            ->select('c.id, c.name')
            ->getQuery()
            ->getArrayResult();
        foreach ($calendars as &$calendar) {
            if (empty($calendar['name'])) {
                $calendar['name'] = $this->entityNameResolver->getName($this->tokenAccessor->getUser());
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
     * @throws AccessDeniedException
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
            if ($systemCalendar->isPublic() && !$this->calendarConfig->isPublicCalendarEnabled()) {
                throw new AccessDeniedException('Public calendars are disabled.');
            }
            if (!$systemCalendar->isPublic() && !$this->calendarConfig->isSystemCalendarEnabled()) {
                throw new AccessDeniedException('System calendars are disabled.');
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
        return $this->doctrine->getRepository('OroCalendarBundle:Calendar')
            ->find($calendarId);
    }

    /**
     * @param int $calendarId
     *
     * @return SystemCalendar|null
     */
    protected function findSystemCalendar($calendarId)
    {
        return $this->doctrine->getRepository('OroCalendarBundle:SystemCalendar')
            ->find($calendarId);
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
        $this->updateManager->onEventUpdate(
            $actualEvent,
            $originalEvent,
            $organization,
            $allowUpdateExceptions
        );
    }
}
