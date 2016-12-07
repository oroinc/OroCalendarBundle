<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventRepositoryTest extends OrmTestCase
{
    /**
     * @var EntityManagerMock
     */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\CalendarBundle\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            array(
                'OroCalendarBundle' => 'Oro\Bundle\CalendarBundle\Entity'
            )
        );
    }

    public function testGetEventListQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getEventListQueryBuilder();

        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetUserEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt,'
            . ' (CASE WHEN (status.id IS NULL) THEN \'none\' ELSE status.id END) as invitationStatus,'
            . ' IDENTITY(event.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(event.recurringEvent) AS recurringEventId,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . ' event.originalStart, event.cancelled AS isCancelled,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.calendar c'
            . ' LEFT JOIN Oro\Bundle\CalendarBundle\Entity\Attendee relatedAttendee WITH'
            . ' ('
            . 'event.parent is NULL AND '
            . 'event.id = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' OR '
            . '('
            . 'event.parent is NOT NULL AND '
            . 'IDENTITY(event.parent) = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' LEFT JOIN event.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND event.recurrence = r.id)'
            . ' WHERE '
            . '((event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end)) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (event.originalStart IS NOT NULL AND event.originalStart <= :endDate AND'
            . ' event.originalStart >= :startDate)'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt,'
            . ' (CASE WHEN (status.id IS NULL) THEN \'none\' ELSE status.id END) as invitationStatus,'
            . ' IDENTITY(event.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(event.recurringEvent) AS recurringEventId,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . ' event.originalStart, event.cancelled AS isCancelled,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.calendar c'
            . ' LEFT JOIN Oro\Bundle\CalendarBundle\Entity\Attendee relatedAttendee WITH'
            . ' ('
            . 'event.parent is NULL AND '
            . 'event.id = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' OR '
            . '('
            . 'event.parent is NOT NULL AND '
            . 'IDENTITY(event.parent) = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' LEFT JOIN event.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND event.recurrence = r.id)'
            . ' WHERE '
            . '(event.allDay = :allDay'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (event.originalStart IS NOT NULL AND event.originalStart <= :endDate AND'
            . ' event.originalStart >= :startDate)'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt,'
            . ' (CASE WHEN (status.id IS NULL) THEN \'none\' ELSE status.id END) as invitationStatus,'
            . ' IDENTITY(event.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(event.recurringEvent) AS recurringEventId,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . ' event.originalStart, event.cancelled AS isCancelled,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.calendar c'
            . ' LEFT JOIN Oro\Bundle\CalendarBundle\Entity\Attendee relatedAttendee WITH'
            . ' ('
            . 'event.parent is NULL AND '
            . 'event.id = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' OR '
            . '('
            . 'event.parent is NOT NULL AND '
            . 'IDENTITY(event.parent) = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' LEFT JOIN event.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND event.recurrence = r.id)'
            . ' WHERE '
            . '(event.allDay = :allDay'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (event.originalStart IS NOT NULL AND event.originalStart <= :endDate AND'
            . ' event.originalStart >= :startDate)'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetUserEventListByTimeIntervalQueryBuilderWithAdditionalFields()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getUserEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            [],
            ['status']
        );

        $prefix = CalendarEventRepository::RECURRENCE_FIELD_PREFIX;
        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt, event.status,'
            . ' (CASE WHEN (status.id IS NULL) THEN \'none\' ELSE status.id END) as invitationStatus,'
            . ' IDENTITY(event.parent) AS parentEventId,'
            . ' c.id as calendar,'
            . ' IDENTITY(event.recurringEvent) AS recurringEventId,'
            . ' IDENTITY(relatedAttendee.user) AS relatedAttendeeUserId,'
            . ' event.originalStart, event.cancelled AS isCancelled,'
            . " r.recurrenceType as {$prefix}RecurrenceType, r.interval as {$prefix}Interval,"
            . "r.dayOfWeek as {$prefix}DayOfWeek, r.dayOfMonth as {$prefix}DayOfMonth,"
            . "r.monthOfYear as {$prefix}MonthOfYear, r.startTime as {$prefix}StartTime,"
            . "r.endTime as {$prefix}EndTime, r.occurrences as {$prefix}Occurrences,"
            . "r.instance as {$prefix}Instance, r.id as recurrenceId,"
            . ' r.timeZone as recurrenceTimeZone,'
            . " r.calculatedEndTime as {$prefix}CalculatedEndTime"
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.calendar c'
            . ' LEFT JOIN Oro\Bundle\CalendarBundle\Entity\Attendee relatedAttendee WITH'
            . ' ('
            . 'event.parent is NULL AND '
            . 'event.id = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' OR '
            . '('
            . 'event.parent is NOT NULL AND '
            . 'IDENTITY(event.parent) = IDENTITY(relatedAttendee.calendarEvent) AND '
            . 'IDENTITY(c.owner) = IDENTITY(relatedAttendee.user)'
            . ')'
            . ' LEFT JOIN event.parent parent'
            . ' LEFT JOIN relatedAttendee.status status'
            . ' LEFT JOIN OroCalendarBundle:Recurrence r WITH (parent.id IS NOT NULL AND parent.recurrence = r.id) OR'
            . ' (parent.id IS NULL AND event.recurrence = r.id)'
            . ' WHERE '
            . '((event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end)) OR'
            . ' (r.startTime <= :endDate AND r.calculatedEndTime >= :startDate) OR'
            . ' (event.originalStart IS NOT NULL AND event.originalStart <= :endDate AND'
            . ' event.originalStart >= :startDate)'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND event.allDay = :allDay'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetSystemEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND event.allDay = :allDay'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilder()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(new \DateTime(), new \DateTime());

        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsCriteria()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            new Criteria(Criteria::expr()->eq('allDay', true))
        );

        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND event.allDay = :allDay'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetPublicEventListByTimeIntervalQueryBuilderWithAdditionalFiltersAsArray()
    {
        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder(
            new \DateTime(),
            new \DateTime(),
            ['allDay' => true]
        );

        $this->assertEquals(
            'SELECT event.id, event.title, event.description, event.start, event.end, event.allDay,'
            . ' event.backgroundColor, event.createdAt, event.updatedAt, c.id as calendar'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.systemCalendar c'
            . ' WHERE '
            . 'c.public = :public AND event.allDay = :allDay'
            . ' AND ('
            . '(event.start < :start AND event.end >= :start) OR '
            . '(event.start <= :end AND event.end > :end) OR'
            . '(event.start >= :start AND event.end < :end))'
            . ' ORDER BY c.id, event.start ASC',
            $qb->getQuery()->getDQL()
        );

        $this->assertTrue($qb->getQuery()->getParameter('allDay')->getValue());
    }

    public function testGetInvitedUsersByParentsQueryBuilder()
    {
        $parentEventIds = [1, 2];

        /** @var CalendarEventRepository $repo */
        $repo = $this->em->getRepository('OroCalendarBundle:CalendarEvent');

        $qb = $repo->getInvitedUsersByParentsQueryBuilder($parentEventIds);

        $this->assertEquals(
            'SELECT IDENTITY(event.parent) AS parentEventId, event.id AS eventId, u.id AS userId'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarEvent event'
            . ' INNER JOIN event.calendar c'
            . ' INNER JOIN c.owner u'
            . ' WHERE event.parent IN (:parentEventIds)',
            $qb->getQuery()->getDQL()
        );

        $this->assertEquals($parentEventIds, $qb->getQuery()->getParameter('parentEventIds')->getValue());
    }
}
