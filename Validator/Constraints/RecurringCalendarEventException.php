<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check cases related to exception of recurring calendar event.
 */
class RecurringCalendarEventException extends Constraint
{
    public string $selfRelationMessage =
        'Parameter \'recurringEventId\' can\'t have the same value as calendar event ID.';
    public string $wrongRecurrenceMessage =
        'Parameter \'recurringEventId\' can be set only for recurring calendar events.';
    public string $cantChangeCalendarMessage =
        'Calendar of calendar event exception can not be changed.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
