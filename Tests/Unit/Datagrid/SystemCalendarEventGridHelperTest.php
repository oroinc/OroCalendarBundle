<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\SystemCalendarEventGridHelper;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarEventGridHelperTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private SystemCalendarEventGridHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->helper = new SystemCalendarEventGridHelper($this->authorizationChecker);
    }

    /**
     * @dataProvider getPublicActionConfigurationClosureProvider
     */
    public function testGetPublicActionConfigurationClosure(bool $isGranted, array $expected): void
    {
        $record = $this->createMock(ResultRecordInterface::class);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->willReturn($isGranted);

        $closure = $this->helper->getPublicActionConfigurationClosure();
        $result = $closure($record);
        $this->assertEquals($expected, $result);
    }

    public function getPublicActionConfigurationClosureProvider(): array
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
    public function testGetSystemActionConfigurationClosure(bool $isGranted, array $expected): void
    {
        $record = $this->createMock(ResultRecordInterface::class);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->willReturn($isGranted);

        $closure = $this->helper->getSystemActionConfigurationClosure();
        $result = $closure($record);
        $this->assertEquals($expected, $result);
    }

    public function getSystemActionConfigurationClosureProvider(): array
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
