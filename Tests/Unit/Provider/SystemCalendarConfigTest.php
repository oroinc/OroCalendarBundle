<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use PHPUnit\Framework\TestCase;

class SystemCalendarConfigTest extends TestCase
{
    /**
     * @dataProvider configProvider
     */
    public function testConfig(
        string|bool $enabledSystemCalendar,
        bool $expectedIsPublicCalendarEnabled,
        bool $expectedIsSystemCalendarEnabled
    ): void {
        $config = new SystemCalendarConfig($enabledSystemCalendar);
        $this->assertSame($expectedIsPublicCalendarEnabled, $config->isPublicCalendarEnabled());
        $this->assertSame($expectedIsSystemCalendarEnabled, $config->isSystemCalendarEnabled());
    }

    public function configProvider(): array
    {
        return [
            [false, false, false],
            [true, true, true],
            ['system', true, false],
            ['organization', false, true],
        ];
    }
}
