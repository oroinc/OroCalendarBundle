<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RecurringCalendarEventExceptionConstraint extends Constraint
{
    public $selfRelationMessage = 'Parameter \'recurringEventId\' can\'t have the same value as calendar event ID.';
    public $wrongRecurrenceMessage = 'Parameter \'recurringEventId\' can be set only for recurring calendar events.';
    public $cantChangeCalendarMessage = 'Calendar of calendar event exception can not be changed.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_calendar.recurring_calendar_event_exception_validator';
    }
}
