<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\EventListener\Datagrid\SystemCalendarGridListener;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SystemCalendarGridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var SystemCalendarConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarConfig;

    /** @var SystemCalendarGridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->calendarConfig = $this->createMock(SystemCalendarConfig::class);

        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn(1);

        $this->listener = new SystemCalendarGridListener(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->calendarConfig
        );
    }

    public function testOnBuildBeforeBothPublicAndSystemCalendarsEnabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);

        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects($this->never())
            ->method('offsetUnsetByPath');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    /**
     * @dataProvider disableCalendarProvider
     */
    public function testOnBuildBeforeAnyPublicOrSystemCalendarDisabled(
        bool $isPublicSupported,
        bool $isSystemSupported
    ) {
        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->willReturn($isPublicSupported);
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->willReturn($isSystemSupported);

        $datagrid = $this->createMock(DatagridInterface::class);
        $config = $this->createMock(DatagridConfiguration::class);

        $config->expects($this->exactly(3))
            ->method('offsetUnsetByPath')
            ->withConsecutive(
                ['[columns][public]'],
                ['[filters][columns][public]'],
                ['[sorters][columns][public]']
            );

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    public function disableCalendarProvider(): array
    {
        return [
            [true, false],
            [false, true],
        ];
    }

    public function testOnBuildAfterBothPublicAndSystemGranted()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['oro_public_calendar_management', null, true],
                ['oro_system_calendar_management', null, true],
            ]);

        $qb = $this->createMock(QueryBuilder::class);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('(sc.public = :public OR sc.organization = :organizationId)')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['public', true],
                ['organizationId', 1]
            )
            ->willReturnSelf();

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterBothPublicAndSystemEnabledButSystemNotGranted()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['oro_public_calendar_management'],
                ['oro_system_calendar_management']
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $qb = $this->createMock(QueryBuilder::class);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('sc.public = :public')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('public', true);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterPublicDisabled()
    {
        $organizationId = 1;

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(false);
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap([
                ['oro_public_calendar_management', null, true],
                ['oro_system_calendar_management', null, true],
            ]);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn($organizationId);

        $qb = $this->createMock(QueryBuilder::class);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('sc.organization = :organizationId')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('organizationId', $organizationId);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterSystemDisabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->willReturn(true);

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(false);

        $qb = $this->createMock(QueryBuilder::class);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('sc.public = :public')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('public', true);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterBothPublicAndSystemDisabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(false);
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(false);

        $qb = $this->createMock(QueryBuilder::class);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('1 = 0')
            ->willReturnSelf();

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testGetActionConfigurationClosurePublicGranted()
    {
        $resultRecord = new ResultRecord(['public' => true]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->willReturn(true);

        $closure = $this->listener->getActionConfigurationClosure();
        $this->assertEquals(
            [],
            $closure($resultRecord)
        );
    }

    public function testGetActionConfigurationClosurePublicNotGranted()
    {
        $resultRecord = new ResultRecord(['public' => true]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->willReturn(false);

        $closure = $this->listener->getActionConfigurationClosure();
        $this->assertEquals(
            [
                'update' => false,
                'delete' => false,
            ],
            $closure($resultRecord)
        );
    }

    /**
     * @dataProvider getActionConfigurationClosureSystemProvider
     */
    public function testGetActionConfigurationClosureSystem(bool $allowed, array $expected)
    {
        $resultRecord = new ResultRecord(['public' => false]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->willReturn($allowed);

        $closure = $this->listener->getActionConfigurationClosure();
        $this->assertEquals(
            $expected,
            $closure($resultRecord)
        );
    }

    public function getActionConfigurationClosureSystemProvider(): array
    {
        return [
            [true, []],
            [false, ['update' => false, 'delete' => false]],
        ];
    }
}
