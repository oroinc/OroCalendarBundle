<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarPropertyRepository;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class CalendarPropertyRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));
    }

    public function testGetConnectionsByTargetCalendarQueryBuilder()
    {
        $targetCalendarId = 123;

        /** @var CalendarPropertyRepository $repo */
        $repo = $this->em->getRepository(CalendarProperty::class);
        $qb = $repo->getConnectionsByTargetCalendarQueryBuilder($targetCalendarId);

        $this->assertEquals(
            'SELECT connection'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarProperty connection'
            . ' WHERE connection.targetCalendar = :targetCalendarId',
            $qb->getDQL()
        );
        $this->assertEquals($targetCalendarId, $qb->getParameter('targetCalendarId')->getValue());
    }

    public function testGetConnectionsByTargetCalendarQueryBuilderWithAlias()
    {
        $targetCalendarId = 123;
        $alias = 'test';

        /** @var CalendarPropertyRepository $repo */
        $repo = $this->em->getRepository(CalendarProperty::class);
        $qb = $repo->getConnectionsByTargetCalendarQueryBuilder($targetCalendarId, $alias);

        $this->assertEquals(
            'SELECT connection'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\CalendarProperty connection'
            . ' WHERE connection.targetCalendar = :targetCalendarId AND connection.calendarAlias = :alias',
            $qb->getDQL()
        );
        $this->assertEquals($targetCalendarId, $qb->getParameter('targetCalendarId')->getValue());
        $this->assertEquals($alias, $qb->getParameter('alias')->getValue());
    }
}
