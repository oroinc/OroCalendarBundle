<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class CalendarRepositoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
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
