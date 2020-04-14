<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurringCalendarEventExceptionConstraint;
use Symfony\Component\Validator\Constraint;

class RecurringCalendarEventExceptionConstraintTest extends \PHPUnit\Framework\TestCase
{
    /** @var RecurringCalendarEventExceptionConstraint */
    protected $constraint;

    protected function setUp(): void
    {
        $this->constraint = new RecurringCalendarEventExceptionConstraint();
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'oro_calendar.recurring_calendar_event_exception_validator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
