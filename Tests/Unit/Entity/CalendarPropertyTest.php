<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class CalendarPropertyTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            'id'              => ['id', 1],
            'targetCalendar'  => ['targetCalendar', $this->createMock(Calendar::class)],
            'calendarAlias'   => ['calendarAlias', 'testAlias'],
            'calendar'        => ['calendar', 123],
            'position'        => ['position', 100],
            'visible'         => ['visible', false],
            'backgroundColor' => ['backgroundColor', '#FFFFFF'],
        ];

        $entity = new CalendarProperty();
        self::assertPropertyAccessors($entity, $properties);
    }

    public function testToString(): void
    {
        $entity = new CalendarProperty();
        self::assertSame('', (string)$entity);

        ReflectionUtil::setId($entity, 1);
        self::assertSame('1', (string)$entity);
    }
}
