<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class SystemCalendarRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    public function testGetSystemCalendarsByIdsQueryBuilder()
    {
        /** @var SystemCalendarRepository $repo */
        $repo = $this->em->getRepository(SystemCalendar::class);

        $qb = $repo->getSystemCalendarsByIdsQueryBuilder([]);

        $this->assertEquals(
            'SELECT sc'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\SystemCalendar sc'
            . ' WHERE sc.public = :public AND 1 = 0',
            $qb->getQuery()->getDQL()
        );

        $qb = $repo->getSystemCalendarsByIdsQueryBuilder([1, 2]);

        $this->assertEquals(
            'SELECT sc'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\SystemCalendar sc'
            . ' WHERE sc.public = :public AND sc.id IN(:calendarIds)',
            $qb->getQuery()->getDQL()
        );
        $this->assertFalse($qb->getParameter('public')->getValue());
    }

    public function testGetSystemCalendarsQueryBuilder()
    {
        $organizationId = 1;

        /** @var SystemCalendarRepository $repo */
        $repo = $this->em->getRepository(SystemCalendar::class);

        $qb = $repo->getSystemCalendarsQueryBuilder($organizationId);

        $this->assertEquals(
            'SELECT sc'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\SystemCalendar sc'
            . ' WHERE sc.organization = :organizationId AND sc.public = :public',
            $qb->getQuery()->getDQL()
        );
        $this->assertEquals($organizationId, $qb->getParameter('organizationId')->getValue());
        $this->assertFalse($qb->getParameter('public')->getValue());
    }

    public function testGetPublicCalendarsQueryBuilder()
    {
        /** @var SystemCalendarRepository $repo */
        $repo = $this->em->getRepository(SystemCalendar::class);

        $qb = $repo->getPublicCalendarsQueryBuilder();

        $this->assertEquals(
            'SELECT sc'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\SystemCalendar sc'
            . ' WHERE sc.public = :public',
            $qb->getQuery()->getDQL()
        );
        $this->assertTrue($qb->getParameter('public')->getValue());
    }

    public function testGetCalendarsQueryBuilder()
    {
        $organizationId = 1;

        /** @var SystemCalendarRepository $repo */
        $repo = $this->em->getRepository(SystemCalendar::class);

        $qb = $repo->getCalendarsQueryBuilder($organizationId);

        $this->assertEquals(
            'SELECT sc'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\SystemCalendar sc'
            . ' WHERE sc.organization = :organizationId OR sc.public = :public',
            $qb->getQuery()->getDQL()
        );
        $this->assertEquals($organizationId, $qb->getParameter('organizationId')->getValue());
        $this->assertTrue($qb->getParameter('public')->getValue());
    }
}
