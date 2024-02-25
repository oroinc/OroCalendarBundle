<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * UniqueUid constraint
 *
 * @Annotation
 */
#[Attribute]
class UniqueUid extends Constraint
{
    public $message = 'UID field should be unique across one calendar';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return static::CLASS_CONSTRAINT;
    }
}
