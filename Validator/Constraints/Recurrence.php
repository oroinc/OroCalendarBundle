<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Recurrence extends Constraint
{
    public $minMessage = 'This value should be {{ limit }} or more.';
    public $maxMessage = 'This value should be {{ limit }} or less.';
    public $notBlankMessage = 'This value should not be blank.';
    public $multipleOfMessage = 'This value should be a multiple of {{ multiple_of_value }}.';
    public $choiceMessage = 'This value should be one of the values: {{ allowed_values }}.';
    public $multipleChoicesMessage = 'One or more of the given values is not one of the values: {{ allowed_values }}.';

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
        return 'oro_calendar.recurrence_validator';
    }
}
