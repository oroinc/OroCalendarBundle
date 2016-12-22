<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurringCalendarEventExceptionConstraint;

class RecurringCalendarEventExceptionConstraintTest extends \PHPUnit_Framework_TestCase
{
    /** @var RecurringCalendarEventExceptionConstraint */
    protected $constraint;

    protected function setUp()
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
