<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class for validating calendar event reminder setting time
 */
class ReminderStartDateConstraintValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ReminderStartDate) {
            throw new UnexpectedTypeException($constraint, ReminderStartDate::class);
        }

        $data = $this->context->getRoot();

        if ($data instanceof Form) {
            $start = $data->getData()?->getStart();
        } else {
            $start = $data?->getStart();
        }

        foreach ($value as $key => $reminder) {
            if (!$reminder instanceof Reminder) {
                throw new UnexpectedTypeException($reminder, Reminder::class);
            }

            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $interval = $reminder->getInterval()->createDateInterval();

            $clonedStartDate = $start ? clone $start : null;
            if ($now > $clonedStartDate?->sub($interval)) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath(sprintf('[%d].interval.number', $key))
                    ->addViolation();
            }
        }
    }
}
