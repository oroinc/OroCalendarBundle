<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThan;

class DateEarlierThanTest extends \PHPUnit\Framework\TestCase
{
    public function testGetDefaultOption()
    {
        $field = 'field';
        $constrains = new DateEarlierThan([$field => 'field-value']);
        $this->assertEquals($field, $constrains->getDefaultOption());
    }

    public function testGetRequiredOptions()
    {
        $field = 'field';
        $constrains = new DateEarlierThan([$field => 'field-value']);
        $this->assertEquals([$field], $constrains->getRequiredOptions());
    }
}
