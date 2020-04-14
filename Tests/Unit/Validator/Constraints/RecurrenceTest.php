<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Validator\Constraints\Recurrence;
use Symfony\Component\Validator\Constraint;

class RecurrenceTest extends \PHPUnit\Framework\TestCase
{
    /** @var Recurrence */
    protected $constraint;

    protected function setUp(): void
    {
        $this->constraint = new Recurrence();
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_calendar.recurrence_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
