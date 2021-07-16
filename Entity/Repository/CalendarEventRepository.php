<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Doctrine repository for Calendar entity.
 */
class CalendarEventRepository extends EntityRepository
{
    const RECURRENCE_FIELD_PREFIX = 'recurrence';

    /**
     * Returns a query builder which can be used to get a list of user calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getUserEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [], $extraFields = [])
    {
        $qb = $this->getUserEventListQueryBuilder($filters, $extraFields);
        $this->addTimeIntervalFilter($qb, $startDate, $endDate);

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of user calendar events
     *
     * @param array|Criteria $filters Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getUserEventListQueryBuilder($filters = [], $extraFields = [])
    {
        $qb = $this->getEventListQueryBuilder($filters, $extraFields)
            ->addSelect(
                sprintf(
                    'COALESCE(status.id, \'%s\') as invitationStatus',
                    Attendee::STATUS_NONE
                )
            )
            ->addSelect('IDENTITY(e.parent) AS parentEventId')
            ->addSelect('c.id as calendar')
            ->addSelect('IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId')
            ->leftJoin('e.relatedAttendee', 'relatedAttendee')
            ->leftJoin('e.parent', 'parent')
            ->leftJoin('relatedAttendee.status', 'status')
            ->innerJoin('e.calendar', 'c');

        $this->addRecurrenceData($qb);

        return $qb;
    }

    /**
     * @param CalendarEvent $event
     * @param Calendar|int $calendarId
     * @return CalendarEvent[]
     */
    public function findDuplicatedEvent(CalendarEvent $event, $calendarId)
    {
        $qb = $this->createQueryBuilder('ce');
        $qb
            ->where('ce.uid = :uid')
            ->andWhere('ce.calendar = :calendarId')
            ->andWhere($qb->expr()->isNull('ce.recurringEvent'))
            ->andWhere($qb->expr()->isNull('ce.parent'))
            ->setParameter('uid', $event->getUid())
            ->setParameter('calendarId', $calendarId);

        if ($event->getId()) {
            $qb
                ->andWhere('ce.id != :id')
                ->setParameter('id', $event->getId());
        }

        return $qb->getQuery()->execute();
    }

    /**
     * @param CalendarEvent $event
     * @return CalendarEvent[]
     */
    public function findEventsWithMatchingUidAndOrganizer(CalendarEvent $event)
    {
        if ($event->getUid() === null || $event->getOrganizerEmail() === null) {
            return [];
        }

        $qb = $this->createQueryBuilder('ce');
        $qb->where('ce.uid = :uid')
            ->andWhere('ce.isOrganizer = :isOrganizer')
            ->andWhere('ce.organizerEmail = :organizerEmail')
            ->andWhere($qb->expr()->isNull('ce.parent'))
            ->setParameters(
                [
                    'uid' => $event->getUid(),
                    'isOrganizer' => false,
                    'organizerEmail' => $event->getOrganizerEmail(),
                ]
            );

        return $qb->getQuery()->execute();
    }

    /**
     * Returns a base query builder which can be used to get a list of calendar events
     *
     * @param array|Criteria $filters Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    protected function getEventListQueryBuilder($filters = [], $extraFields = [])
    {
        $qb = $this->createQueryBuilder('e')
            ->select($this->getBaseFields());

        $this->addFilters($qb, $filters);

        if ($extraFields) {
            foreach ($extraFields as $field) {
                $qb->addSelect(QueryBuilderUtil::getField('e', $field));
            }
        }

        return $qb;
    }

    /**
     * Returns list of fields added to all select queries.
     *
     * @return array
     */
    protected function getBaseFields()
    {
        return [
            'e.id',
            'e.uid',
            'e.title',
            'e.description',
            'e.start',
            'e.end',
            'e.allDay',
            'e.backgroundColor',
            'e.createdAt',
            'e.updatedAt',
            'e.originalStart',
            'IDENTITY(e.recurringEvent) AS recurringEventId',
            'e.cancelled AS isCancelled',
            'e.isOrganizer AS isOrganizer',
            'e.organizerEmail',
            'e.organizerDisplayName',
            'IDENTITY(e.organizerUser) as organizerUserId'
        ];
    }

    /**
     * Adds filters to the query builder.
     *
     * @param QueryBuilder   $qb
     * @param array|Criteria $filters Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                or \Doctrine\Common\Collections\Criteria
     */
    protected function addFilters(QueryBuilder $qb, $filters)
    {
        if ($filters) {
            if (is_array($filters)) {
                $newCriteria = new Criteria();
                foreach ($filters as $fieldName => $value) {
                    $newCriteria->andWhere(Criteria::expr()->eq($fieldName, $value));
                }

                $filters = $newCriteria;
            }

            if ($filters instanceof Criteria) {
                $qb->addCriteria($filters);
            }
        }
    }

    /**
     * Adds recurrence select items and joins to the query builder.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return CalendarEventRepository
     */
    protected function addRecurrenceData(QueryBuilder $queryBuilder)
    {
        $prefix = self::RECURRENCE_FIELD_PREFIX;
        $queryBuilder
            ->leftJoin(
                'OroCalendarBundle:Recurrence',
                'r',
                Expr\Join::WITH,
                '(parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            )
            ->addSelect(
                "r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
                . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
                . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
                . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
                . "r.instance as {$prefix}Instance, r.id as {$prefix}Id, r.timeZone as {$prefix}TimeZone"
            );

        return $this;
    }

    /**
     * Adds time condition to a query builder responsible to get calender events.
     *
     * For recurring events adds time conditions for getting recurrence events that could be out of filtering dates.
     */
    protected function addTimeIntervalFilter(QueryBuilder $qb, \DateTime $startDate, \DateTime $endDate)
    {
        $prefix = self::RECURRENCE_FIELD_PREFIX;

        $qb
            ->addSelect("r.calculatedEndTime as {$prefix}CalculatedEndTime")
            ->andWhere(
                '(e.start < :start AND e.end >= :start) OR '
                . '(e.start <= :end AND e.end > :end) OR'
                . '(e.start >= :start AND e.end < :end)'
            )
            //add condition that recurrence dates and filter dates are crossing
            ->orWhere(
                'r.startTime <= :end AND r.calculatedEndTime >= :start'
            )
            ->orWhere(
                'e.originalStart IS NOT NULL AND ' .
                'e.originalStart <= :end AND ' .
                'e.originalStart >= :start'
            )
            ->setParameter('start', $startDate, Types::DATETIME_MUTABLE)
            ->setParameter('end', $endDate, Types::DATETIME_MUTABLE)
            ->orderBy('c.id, e.start');
    }

    /**
     * Returns a query builder which can be used to get a list of user calendar events.
     *
     * In addition if $recurringEventId passed the events will be filtered by recurring event and recurring event
     * will be returned as well.
     *
     * @param Criteria $filters
     * @param array $extraFields
     * @param null|integer $recurringEventId
     *
     * @return QueryBuilder
     */
    public function getUserEventListByRecurringEventQueryBuilder(
        Criteria $filters,
        $extraFields = [],
        $recurringEventId = null
    ) {
        $qb = $this->getUserEventListQueryBuilder($filters, $extraFields);

        $recurringEventId = (int)$recurringEventId;

        if ($recurringEventId) {
            $this->addRecurringEventFilter($qb, $recurringEventId);
        }

        return $qb;
    }

    /**
     * Query builder will be filtered by association with recurring event and the recurring event
     * will be included as well.
     *
     * @param QueryBuilder  $qb
     * @param integer       $recurringEventId
     */
    protected function addRecurringEventFilter(QueryBuilder $qb, $recurringEventId)
    {
        $qb
            ->andWhere('e.recurringEvent = :recurringEventId')
            ->orWhere('e.id = :recurringEventId')
            ->setParameter('recurringEventId', $recurringEventId);
    }

    /**
     * Returns a query builder which can be used to get a list of system calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getSystemEventListByTimeIntervalQueryBuilder(
        $startDate,
        $endDate,
        $filters = [],
        $extraFields = []
    ) {
        return $this->getSystemOrPublicEventListByTimeIntervalQueryBuilder(
            false,
            $startDate,
            $endDate,
            $filters,
            $extraFields
        );
    }

    /**
     * Returns a query builder which can be used to get a list of system or public
     * calendar events filtered by start and end dates.
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param boolean        $public    Filter only events in public calendar or not.
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    protected function getSystemOrPublicEventListByTimeIntervalQueryBuilder(
        $public,
        $startDate,
        $endDate,
        $filters = [],
        $extraFields = []
    ) {
        $qb = $this->getEventListQueryBuilder($filters, $extraFields)
            ->addSelect('c.id as calendar')
            ->innerJoin('e.systemCalendar', 'c')
            ->leftJoin('e.parent', 'parent')
            ->andWhere('c.public = :public')
            ->setParameter('public', $public);

        $this->addRecurrenceData($qb);
        $this->addTimeIntervalFilter($qb, $startDate, $endDate);

        return $qb;
    }

    /**
     * Returns a query builder which can be used to get a list of public calendar events filtered by start and end dates
     *
     * @param \DateTime      $startDate
     * @param \DateTime      $endDate
     * @param array|Criteria $filters   Additional filtering criteria, e.g. ['allDay' => true, ...]
     *                                  or \Doctrine\Common\Collections\Criteria
     * @param array          $extraFields
     *
     * @return QueryBuilder
     */
    public function getPublicEventListByTimeIntervalQueryBuilder($startDate, $endDate, $filters = [], $extraFields = [])
    {
        return $this->getSystemOrPublicEventListByTimeIntervalQueryBuilder(
            true,
            $startDate,
            $endDate,
            $filters,
            $extraFields
        );
    }

    /**
     * @param array $calendarEventIds
     *
     * @return array Map with structure "parentId => [parentId, childId, ...]"
     *               where value is array of items from $calendarEventIds
     */
    public function getParentEventIds(array $calendarEventIds)
    {
        if (!$calendarEventIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('event');

        $queryResult = $qb
            ->select('event.id AS parent, children.id AS child')
            ->join('event.childEvents', 'children')
            ->where($qb->expr()->in('children.id', ':calendarEventIds'))
            ->setParameter('calendarEventIds', $calendarEventIds)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($calendarEventIds as $id) {
            $result[$id][] = $id;
        }

        foreach ($queryResult as $row) {
            $result[$row['parent']][] = $row['child'];
        }

        return $result;
    }
}
