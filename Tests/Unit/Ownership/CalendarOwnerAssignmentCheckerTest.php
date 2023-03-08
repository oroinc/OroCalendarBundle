<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Ownership;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Ownership\CalendarOwnerAssignmentChecker;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class CalendarOwnerAssignmentCheckerTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    /**
     * @dataProvider hasAssignmentsProvider
     */
    public function testHasAssignments(array $records, bool $expectedResult)
    {
        $actualSql = '';
        $statement = $this->createFetchStatementMock($records);
        $this->getDriverConnectionMock($this->em)->expects($this->any())
            ->method('prepare')
            ->willReturnCallback(function ($prepareString) use (&$statement, &$actualSql) {
                $actualSql = $prepareString;

                return $statement;
            });

        $checker = new CalendarOwnerAssignmentChecker();
        $actualResult = $checker->hasAssignments(
            1,
            Calendar::class,
            'owner',
            $this->em
        );

        $expectedSql = 'SELECT o0_.id AS id_0'
            . ' FROM oro_calendar o1_'
            . ' INNER JOIN oro_user o0_ ON o1_.user_owner_id = o0_.id'
            . ' WHERE o0_.id = ? AND'
            . ' (o1_.name IS NOT NULL OR (o1_.name IS NULL AND'
            . ' EXISTS (SELECT o2_.id FROM oro_calendar_event o2_'
            . ' INNER JOIN oro_calendar o3_ ON o2_.calendar_id = o3_.id'
            . ' WHERE o3_.id = o1_.id)))'
            . ' LIMIT 1';
        $this->assertEquals($expectedSql, $actualSql);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function hasAssignmentsProvider(): array
    {
        return [
            [[], false],
            [[['id_0' => '1']], true]
        ];
    }
}
