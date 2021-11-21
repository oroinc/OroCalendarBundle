<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that the calendar event recurrence is valid.
 */
class Recurrence extends Constraint
{
    public string $minMessage = 'This value should be {{ limit }} or more.';
    public string $maxMessage = 'This value should be {{ limit }} or less.';
    public string $notBlankMessage = 'This value should not be blank.';
    public string $multipleOfMessage = 'This value should be a multiple of {{ multiple_of_value }}.';
    public string $choiceMessage = 'This value should be one of the values: {{ allowed_values }}.';
    public string $multipleChoicesMessage =
        'One or more of the given values is not one of the values: {{ allowed_values }}.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
