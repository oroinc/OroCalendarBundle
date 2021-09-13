<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CalendarBundle\Controller\Api\Rest as Api;
use Oro\Bundle\CalendarBundle\DependencyInjection\OroCalendarExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroCalendarExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroCalendarExtension());

        $expectedDefinitions = [
            Api\CalendarConnectionController::class,
            Api\CalendarController::class,
            Api\CalendarEventController::class,
            Api\SystemCalendarController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
