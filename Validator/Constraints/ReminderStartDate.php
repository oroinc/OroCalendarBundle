<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint class for validating calendar event reminder setting time
 */
class ReminderStartDate extends Constraint
{
    public string $message = 'oro.calendar.calendar_event.reminder.date_start_less_than_now.message';

    public function validatedBy(): string
    {
        return ReminderStartDateConstraintValidator::class;
    }
}
