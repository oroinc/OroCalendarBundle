<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\CalendarBundle\EventListener\Datagrid\SystemCalendarGridListener;
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
    /** @var SystemCalendarGridListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarConfig;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));
        $this->calendarConfig =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();

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
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config   = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->never())
            ->method('offsetUnsetByPath');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    /**
     * @dataProvider disableCalendarProvider
     */
    public function testOnBuildBeforeAnyPublicOrSystemCalendarDisabled($isPublicSupported, $isSystemSupported)
    {
        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue($isPublicSupported));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue($isSystemSupported));

        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config   = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->at(0))
            ->method('offsetUnsetByPath')
            ->with('[columns][public]');
        $config->expects($this->at(1))
            ->method('offsetUnsetByPath')
            ->with('[filters][columns][public]');
        $config->expects($this->at(2))
            ->method('offsetUnsetByPath')
            ->with('[sorters][columns][public]');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    public function disableCalendarProvider()
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
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_public_calendar_management', null, true],
                        ['oro_system_calendar_management', null, true],
                    ]
                )
            );

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('(sc.public = :public OR sc.organization = :organizationId)')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('public', true)
            ->will($this->returnSelf());

        $qb->expects($this->at(2))
            ->method('setParameter')
            ->with('organizationId', 1);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function onBuildAfterBothPublicAndSystemGrantedDataProvider()
    {
        return [
            [true, false],
            [false, true],
            [true, true]
        ];
    }

    public function testOnBuildAfterBothPublicAndSystemEnabledButSystemNotGranted()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['oro_public_calendar_management'],
                ['oro_system_calendar_management']
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('sc.public = :public')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
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
            ->will($this->returnValue(false));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValueMap(
                [
                    ['oro_public_calendar_management', null, true],
                    ['oro_system_calendar_management', null, true],
                ]
            ));

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('sc.organization = :organizationId')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('organizationId', $organizationId);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterSystemDisabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->will($this->returnValue(true));

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('sc.public = :public')
            ->will($this->returnSelf());

        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('public', true);

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterBothPublicAndSystemDisabled()
    {
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('1 = 0')
            ->will($this->returnSelf());

        $event = new BuildAfter($datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testGetActionConfigurationClosurePublicGranted()
    {
        $resultRecord = new ResultRecord(['public' => true]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->will($this->returnValue(true));

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
            ->will($this->returnValue(false));

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
    public function testGetActionConfigurationClosureSystem($allowed, $expected)
    {
        $resultRecord = new ResultRecord(['public' => false]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue($allowed));

        $closure = $this->listener->getActionConfigurationClosure();
        $this->assertEquals(
            $expected,
            $closure($resultRecord)
        );
    }

    public function getActionConfigurationClosureSystemProvider()
    {
        return [
            [true, []],
            [false, ['update' => false, 'delete' => false]],
        ];
    }
}
