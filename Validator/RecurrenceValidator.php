<?php

namespace Oro\Bundle\CalendarBundle\Validator;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecurrenceValidator extends ConstraintValidator
{
    const MIN_INTERVAL = 1;

    /**
     * @var Model\Recurrence
     */
    protected $model;

    /**
     * RecurrenceValidator constructor.
     */
    public function __construct(Model\Recurrence $recurrenceModel)
    {
        $this->model = $recurrenceModel;
    }

    /**
     * Validates recurrence according to its recurrenceType.
     *
     * @param Entity\Recurrence $value
     * @param Constraints\Recurrence $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $hasValidRecurrenceType = $this->validateRecurrenceType($value, $constraint);

        if (!$hasValidRecurrenceType) {
            return;
        }

        $this->validateRequiredProperties($value, $constraint);
        $this->validateInterval($value, $constraint);
        $this->validateEndTime($value, $constraint);
        $this->validateDayOfWeek($value, $constraint);
    }

    /**
     * Validates recurrence type.
     *
     * @param Entity\Recurrence $value
     * @return bool
     */
    protected function validateRecurrenceType(Entity\Recurrence $value, Constraints\Recurrence $constraint)
    {
        $recurrenceType = $value->getRecurrenceType();

        $this->validateNotBlank($recurrenceType, $constraint, 'recurrenceType');
        $this->validateChoice($recurrenceType, $this->model->getRecurrenceTypesValues(), $constraint, 'recurrenceType');

        return in_array($recurrenceType, $this->model->getRecurrenceTypesValues());
    }

    /**
     * Validate the value corresponds to allowed list of values.
     *
     * @param mixed $value
     * @param array $allowedValues
     * @param Constraints\Recurrence $constraint
     * @param string $path
     */
    protected function validateChoice($value, array $allowedValues, Constraints\Recurrence $constraint, $path)
    {
        if ($value === null) {
            return;
        }

        if (!in_array($value, $allowedValues)) {
            $this->addViolation(
                $constraint->choiceMessage,
                ['{{ allowed_values }}' => implode(', ', $allowedValues)],
                $value,
                $path
            );
        }
    }

    /**
     * Validates value is not blank.
     *
     * @param mixed $value
     * @param Constraints\Recurrence $constraint
     * @param string $path
     */
    protected function validateNotBlank($value, Constraints\Recurrence $constraint, $path)
    {
        if ($value === null || (is_array($value) && empty($value))) {
            $this->addViolation(
                $constraint->notBlankMessage,
                [],
                $value,
                $path
            );
        }
    }

    /**
     * Validates all required fields are not blank.
     */
    protected function validateRequiredProperties(Entity\Recurrence $recurrence, Constraints\Recurrence $constraint)
    {
        $requiredProperties = $this->model->getRequiredProperties($recurrence);

        foreach ($requiredProperties as $name) {
            $method = 'get' . ucfirst($name);
            $value = $recurrence->$method();
            $this->validateNotBlank($value, $constraint, $name);
        }
    }

    /**
     * Validates interval property.
     */
    protected function validateInterval(Entity\Recurrence $recurrence, Constraints\Recurrence $constraint)
    {
        $interval = $recurrence->getInterval();

        $this->validateRange(
            $interval,
            self::MIN_INTERVAL,
            $this->model->getMaxInterval($recurrence),
            $constraint,
            'interval'
        );

        $multiplier = (int)$this->model->getIntervalMultipleOf($recurrence);
        if ($interval !== null && $multiplier > 1 && $interval % $multiplier !== 0) {
            $this->addViolation(
                $constraint->multipleOfMessage,
                [
                    '{{ multiple_of_value }}' => $multiplier,
                ],
                $interval,
                'interval'
            );
        }
    }

    /**
     * Validates value is between range.
     *
     * @param float|integer $value
     * @param float|integer|null $min
     * @param float|integer|null $max
     * @param Constraints\Recurrence $constraint
     * @param string $path
     */
    protected function validateRange($value, $min, $max, Constraints\Recurrence $constraint, $path)
    {
        if ($value === null || !is_numeric($value)) {
            return;
        } elseif ($min !== null && $value < $min) {
            $this->addViolation(
                $constraint->minMessage,
                [
                    '{{ limit }}' => $min,
                ],
                $value,
                $path
            );
        } elseif ($max !== null && $value > $max) {
            $this->addViolation(
                $constraint->maxMessage,
                [
                    '{{ limit }}' => $max,
                ],
                $value,
                $path
            );
        }
    }

    /**
     * Validates recurrence type.
     */
    protected function validateEndTime(Entity\Recurrence $value, Constraints\Recurrence $constraint)
    {
        $endTime = $value->getEndTime();
        $startTime = $value->getStartTime();

        if (!$endTime instanceof \DateTime || !$startTime instanceof \DateTime) {
            return;
        }

        if ($startTime > $endTime) {
            $this->addViolation(
                $constraint->minMessage,
                [
                    '{{ limit }}' => $startTime->format(\DateTime::RFC3339)
                ],
                $value->getEndTime(),
                'endTime'
            );
        }
    }

    /**
     * Validates day of week type.
     */
    protected function validateDayOfWeek(Entity\Recurrence $value, Constraints\Recurrence $constraint)
    {
        $dayOfWeek = $value->getDayOfWeek();

        $this->validateMultipleChoices(
            $dayOfWeek,
            $this->model->getDaysOfWeekValues(),
            $constraint,
            'dayOfWeek'
        );
    }

    /**
     * Validates tje list of values correspond to allowed list of values.
     *
     * @param mixed $value
     * @param array $allowedValues
     * @param Constraints\Recurrence $constraint
     * @param string $path
     */
    protected function validateMultipleChoices($value, array $allowedValues, Constraints\Recurrence $constraint, $path)
    {
        if ($value === null || !is_array($value)) {
            return;
        }

        if (count(array_intersect($value, $allowedValues)) !== count($value)) {
            $this->addViolation(
                $constraint->multipleChoicesMessage,
                ['{{ allowed_values }}' => implode(', ', $allowedValues)],
                $value,
                $path
            );
        }
    }

    /**
     * @param string $message
     * @param array $parameters
     * @param string|null $invalidValue
     * @param string|null $path
     */
    protected function addViolation($message, array $parameters = [], $invalidValue = null, $path = null)
    {
        if ($this->context instanceof ExecutionContextInterface) {
            $violationBuilder = $this->context->buildViolation($message)
                ->setParameters($parameters)
                ->setInvalidValue($invalidValue);

            if ($path) {
                $violationBuilder->atPath($path);
            }

            $violationBuilder->addViolation();
        } else {
            /** @var  $violationBuilder */
            $violationBuilder = $this->context->buildViolation($message)
                ->setInvalidValue($invalidValue)
                ->setParameters($parameters);

            if ($path) {
                $violationBuilder->atPath($path);
            }

            $violationBuilder->addViolation();
        }
    }
}
