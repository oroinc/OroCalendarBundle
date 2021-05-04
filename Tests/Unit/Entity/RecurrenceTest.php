<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class RecurrenceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            'id'                => ['id', 1],
            'recurrenceType'    => ['recurrenceType', 'daily'],
            'interval'          => ['interval', 99],
            'instance'          => ['instance', 3],
            'dayOfWeek'         => ['dayOfWeek', ['monday', 'wednesday']],
            'dayOfMonth'        => ['dayOfMonth', 28],
            'monthOfYear'       => ['monthOfYear', 8],
            'startTime'         => ['startTime', new \DateTime()],
            'endTime'           => ['endTime', new \DateTime()],
            'calculatedEndTime' => ['calculatedEndTime', new \DateTime()],
            'calendarEvent'     => ['calendarEvent', new CalendarEvent()],
            'occurrences'       => ['occurrences', 1],
        ];

        $entity = new Recurrence();
        self::assertPropertyAccessors($entity, $properties);
    }
}
