<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Doctrine repository for Attendee entity.
 */
class AttendeeRepository extends EntityRepository
{
    /**
     * @param Organization|null $organization
     * @param string|null $query
     * @param int|null $limit
     *
     * @return array
     */
    public function getEmailRecipients(
        ?Organization $organization = null,
        $query = null,
        $limit = null
    ) {
        $qb = $this->createQueryBuilder('a')
            ->select('a.email, a.displayName AS name')
            ->groupBy('a.email, a.displayName');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($query) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like('a.displayName', ':query'),
                    $qb->expr()->like('a.email', ':query')
                ))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        if ($organization) {
            $qb
                ->join('a.calendarEvent', 'e')
                ->join('e.calendar', 'c')
                ->join('c.organization', 'o')
                ->andWhere('o.id = :organization')
                ->setParameter('organization', $organization);
        }

        return $qb->getQuery()
            ->getArrayResult();
    }

    /**
     * @param array $calendarEventIds
     *
     * @return QueryBuilder
     */
    public function createAttendeeListsQb(array $calendarEventIds)
    {
        $qb = $this->createQueryBuilder('attendee');

        return $qb
            ->select('attendee.displayName, attendee.email, attendee.createdAt, attendee.updatedAt')
            ->addSelect('attendee_status.internalId as status, attendee_type.internalId as type')
            ->addSelect('event.id as calendarEventId')
            ->join('attendee.calendarEvent', 'event')
            ->leftJoin(
                EnumOption::class,
                'attendee_status',
                Expr\Join::WITH,
                "JSON_EXTRACT(attendee.serialized_data, 'status') = attendee_status"
            )
            ->leftJoin(
                EnumOption::class,
                'attendee_type',
                Expr\Join::WITH,
                "JSON_EXTRACT(attendee.serialized_data, 'type') = attendee_type"
            )
            ->where($qb->expr()->in('event.id', ':calendar_event'))
            ->setParameter('calendar_event', $calendarEventIds);
    }

    /**
     * @param CalendarEvent $calendarEvent
     * @return array
     */
    public function getAttendeesForCalendarEvent(CalendarEvent $calendarEvent)
    {
        $qb = $this->createQueryBuilder('attendee');

        return $qb
            ->select('attendee')
            ->where('attendee.calendarEvent = :event')
            ->setParameter('event', $calendarEvent)
            ->getQuery()
            ->getResult();
    }
}
