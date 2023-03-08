<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    public function testGetUserEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' COALESCE(status.id, \'none\') as invitationStatus,'
            . ' IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '((e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end)) OR'
            . ' (r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND'
            . ' e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' COALESCE(status.id, \'none\') as invitationStatus,'
            . ' IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '(e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))) OR'
            . ' (r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND'
            . ' e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' COALESCE(status.id, \'none\') as invitationStatus,'
            . ' IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '(e.allDay = :allDay'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end))) OR'
            . ' (r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND'
            . ' e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFields()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            [],
            ['status']
        );

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' e.status,'
            . ' COALESCE(status.id, \'none\') as invitationStatus,'
            . ' IDENTITY(e.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' LEFT JOIN e.relatedAttendee relatedAttendee'
            . ' LEFT JOIN e.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' INNER JOIN e.calendar c'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '((e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end)) OR'
            . ' (r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND'
            . ' e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' c.id as calendar,'
            . ' r.recurrenceType as recurrenceRecurrenceType, r.interval as recurrenceInterval,'
            . 'r.dayOfWeek as recurrenceDayOfWeek, r.dayOfMonth as recurrenceDayOfMonth,'
            . 'r.monthOfYear as recurrenceMonthOfYear, r.startTime as recurrenceStartTime,'
            . 'r.endTime as recurrenceEndTime, r.occurrences as recurrenceOccurrences,'
            . 'r.instance as recurrenceInstance, r.id as recurrenceId, r.timeZone as recurrenceTimeZone,'
            . ' r.calculatedEndTime as recurrenceCalculatedEndTime'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' LEFT JOIN e.parent parent LEFT JOIN OroCalendarBundle:Recurrence r WITH'
            . ' (parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '(c.public = :public'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end)))'
            . ' OR (r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' c.id as calendar,'
            . ' r.recurrenceType as recurrenceRecurrenceType, r.interval as recurrenceInterval,'
            . 'r.dayOfWeek as recurrenceDayOfWeek, r.dayOfMonth as recurrenceDayOfMonth,'
            . 'r.monthOfYear as recurrenceMonthOfYear, r.startTime as recurrenceStartTime,'
            . 'r.endTime as recurrenceEndTime, r.occurrences as recurrenceOccurrences,'
            . 'r.instance as recurrenceInstance, r.id as recurrenceId, r.timeZone as recurrenceTimeZone,'
            . ' r.calculatedEndTime as recurrenceCalculatedEndTime'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' LEFT JOIN e.parent parent LEFT JOIN OroCalendarBundle:Recurrence r WITH'
            . ' (parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '(e.allDay = :allDay AND c.public = :public'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end)))'
            . ' OR (r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' c.id as calendar,'
            . ' r.recurrenceType as recurrenceRecurrenceType, r.interval as recurrenceInterval,'
            . 'r.dayOfWeek as recurrenceDayOfWeek, r.dayOfMonth as recurrenceDayOfMonth,'
            . 'r.monthOfYear as recurrenceMonthOfYear, r.startTime as recurrenceStartTime,'
            . 'r.endTime as recurrenceEndTime, r.occurrences as recurrenceOccurrences,'
            . 'r.instance as recurrenceInstance, r.id as recurrenceId, r.timeZone as recurrenceTimeZone,'
            . ' r.calculatedEndTime as recurrenceCalculatedEndTime'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' LEFT JOIN e.parent parent LEFT JOIN OroCalendarBundle:Recurrence r WITH'
            . ' (parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE '
            . '(e.allDay = :allDay AND c.public = :public'
            . ' AND ('
            . '(e.start < :start AND e.end >= :start) OR '
            . '(e.start <= :end AND e.end > :end) OR'
            . '(e.start >= :start AND e.end < :end)))'
            . ' OR (r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' c.id as calendar,'
            . ' r.recurrenceType as recurrenceRecurrenceType, r.interval as recurrenceInterval,'
            . 'r.dayOfWeek as recurrenceDayOfWeek, r.dayOfMonth as recurrenceDayOfMonth,'
            . 'r.monthOfYear as recurrenceMonthOfYear, r.startTime as recurrenceStartTime,'
            . 'r.endTime as recurrenceEndTime, r.occurrences as recurrenceOccurrences,'
            . 'r.instance as recurrenceInstance, r.id as recurrenceId, r.timeZone as recurrenceTimeZone,'
            . ' r.calculatedEndTime as recurrenceCalculatedEndTime'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' LEFT JOIN e.parent parent LEFT JOIN OroCalendarBundle:Recurrence r WITH'
            . ' (parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE (c.public = :public AND ((e.start < :start AND e.end >= :start)'
            . ' OR (e.start <= :end AND e.end > :end) OR(e.start >= :start AND e.end < :end))) OR '
            . '(r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' c.id as calendar,'
            . ' r.recurrenceType as recurrenceRecurrenceType, r.interval as recurrenceInterval,'
            . 'r.dayOfWeek as recurrenceDayOfWeek, r.dayOfMonth as recurrenceDayOfMonth,'
            . 'r.monthOfYear as recurrenceMonthOfYear, r.startTime as recurrenceStartTime,'
            . 'r.endTime as recurrenceEndTime, r.occurrences as recurrenceOccurrences,'
            . 'r.instance as recurrenceInstance, r.id as recurrenceId, r.timeZone as recurrenceTimeZone,'
            . ' r.calculatedEndTime as recurrenceCalculatedEndTime'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' LEFT JOIN e.parent parent LEFT JOIN OroCalendarBundle:Recurrence r WITH'
            . ' (parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE (e.allDay = :allDay AND c.public = :public AND ((e.start < :start AND e.end >= :start)'
            . ' OR (e.start <= :end AND e.end > :end) OR(e.start >= :start AND e.end < :end))) OR '
            . '(r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository(CalendarEvent::class);

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $this->assertEquals(
            $this->getBaseSelectString() . ','
            . ' c.id as calendar,'
            . ' r.recurrenceType as recurrenceRecurrenceType, r.interval as recurrenceInterval,'
            . 'r.dayOfWeek as recurrenceDayOfWeek, r.dayOfMonth as recurrenceDayOfMonth,'
            . 'r.monthOfYear as recurrenceMonthOfYear, r.startTime as recurrenceStartTime,'
            . 'r.endTime as recurrenceEndTime, r.occurrences as recurrenceOccurrences,'
            . 'r.instance as recurrenceInstance, r.id as recurrenceId, r.timeZone as recurrenceTimeZone,'
            . ' r.calculatedEndTime as recurrenceCalculatedEndTime'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent e'
            . ' INNER JOIN e.systemCalendar c'
            . ' LEFT JOIN e.parent parent LEFT JOIN OroCalendarBundle:Recurrence r WITH'
            . ' (parent.id IS NOT NULL AND parent.recurrence = r.id) OR (parent.id IS NULL AND e.recurrence = r.id)'
            . ' WHERE (e.allDay = :allDay AND c.public = :public AND ((e.start < :start AND e.end >= :start)'
            . ' OR (e.start <= :end AND e.end > :end) OR(e.start >= :start AND e.end < :end))) OR '
            . '(r.startTime <= :end AND r.calculatedEndTime >= :start) OR'
            . ' (e.originalStart IS NOT NULL AND e.originalStart <= :end AND e.originalStart >= :start)'
            . ' ORDER BY c.id, e.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    private function getBaseSelectString(): string
    {
        $baseFields = [
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

        return 'SELECT ' . implode(', ', $baseFields);
    }
}
