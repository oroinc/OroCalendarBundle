<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * EventAttendees constraint
 *
 * @Annotation
 */
#[Attribute]
class EventAttendees extends Constraint
{
    public $message = 'Attendees list cannot be changed';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return static::CLASS_CONSTRAINT;
    }
}
