<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\SystemCalendarEventGridHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarEventGridHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var SystemCalendarEventGridHelper */
    protected $helper;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->helper = new SystemCalendarEventGridHelper($this->authorizationChecker);
    }

    /**
     * @dataProvider getPublicActionConfigurationClosureProvider
     */
    public function testGetPublicActionConfigurationClosure($isGranted, $expected)
    {
        $record = $this->createMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->will($this->returnValue($isGranted));

        $closure = $this->helper->getPublicActionConfigurationClosure();
        $result  = call_user_func($closure, $record);
        $this->assertEquals($expected, $result);
    }

    public function getPublicActionConfigurationClosureProvider()
    {
        return [
            [
                false,
                [
                    'update' => false,
                    'delete' => false,
                ]
            ],
            [true, []]
        ];
    }

    /**
     * @dataProvider getSystemActionConfigurationClosureProvider
     */
    public function testGetSystemActionConfigurationClosure($isGranted, $expected)
    {
        $record = $this->createMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue($isGranted));

        $closure = $this->helper->getSystemActionConfigurationClosure();
        $result  = call_user_func($closure, $record);
        $this->assertEquals($expected, $result);
    }

    public function getSystemActionConfigurationClosureProvider()
    {
        return [
            [
                false,
                [
                    'update' => false,
                    'delete' => false,
                ]
            ],
            [true, []]
        ];
    }
}
