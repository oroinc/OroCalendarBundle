<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class CalendarRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));
    }

    public function testGetUserCalendarsQueryBuilder()
    {
        $organizationId = 1;
        $userId = 123;

        /** @var CalendarRepository $repo */
        $repo = $this->em->getRepository(Calendar::class);

        $qb = $repo->getUserCalendarsQueryBuilder($organizationId, $userId);

        $this->assertEquals(
            'SELECT c'
            . ' FROM Oro\Bundle\CalendarBundle\Entity\Calendar c'
            . ' WHERE c.organization = :organizationId AND c.owner = :userId',
            $qb->getQuery()->getDQL()
        );
        $this->assertEquals($organizationId, $qb->getParameter('organizationId')->getValue());
        $this->assertEquals($userId, $qb->getParameter('userId')->getValue());
    }
}
