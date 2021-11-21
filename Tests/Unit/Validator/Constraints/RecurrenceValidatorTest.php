<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\CalendarBundle\Validator\Constraints\Recurrence;
use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurrenceValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RecurrenceValidatorTest extends ConstraintValidatorTestCase
{
    private static $expectedRecurrenceTypesValues = [
        Model\Recurrence::TYPE_DAILY,
        Model\Recurrence::TYPE_WEEKLY,
        Model\Recurrence::TYPE_MONTHLY,
        Model\Recurrence::TYPE_MONTH_N_TH,
        Model\Recurrence::TYPE_YEARLY,
        Model\Recurrence::TYPE_YEAR_N_TH,
    ];

    private static $expectedDaysOfWeekValues = [
        Model\Recurrence::DAY_SUNDAY,
        Model\Recurrence::DAY_MONDAY,
        Model\Recurrence::DAY_TUESDAY,
        Model\Recurrence::DAY_WEDNESDAY,
        Model\Recurrence::DAY_THURSDAY,
        Model\Recurrence::DAY_FRIDAY,
        Model\Recurrence::DAY_SATURDAY,
    ];

    /** @var Model\Recurrence|\PHPUnit\Framework\MockObject\MockObject */
    private $model;

    protected function setUp(): void
    {
        $this->model = $this->createMock(Model\Recurrence::class);
        $this->model->expects(self::any())
            ->method('getRecurrenceTypesValues')
            ->willReturn(self::$expectedRecurrenceTypesValues);
        $this->model->expects(self::any())
            ->method('getDaysOfWeekValues')
            ->willReturn(self::$expectedDaysOfWeekValues);

        parent::setUp();

        $this->setPropertyPath('');
    }

    protected function createValidator(): RecurrenceValidator
    {
        return new RecurrenceValidator($this->model);
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Entity\Recurrence::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotRecurrenceEntity()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new Recurrence());
    }

    public function testRecurrenceHasNoErrors(): void
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setStartTime(new \DateTime());
        $recurrence->setInterval(1);
        $recurrence->setTimeZone('UTC');

        $this->model->expects(self::once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn(['recurrenceType', 'interval', 'timeZone', 'startTime']);
        $this->model->expects(self::once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $this->validator->validate($recurrence, new Recurrence());

        $this->assertNoViolation();
    }

    public function testRecurrenceHasBlankRecurrenceType(): void
    {
        $recurrence = new Entity\Recurrence();

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this->buildViolation($constraint->notBlankMessage)
            ->setInvalidValue(null)
            ->atPath('recurrenceType')
            ->assertRaised();
    }

    public function testRecurrenceHasWrongRecurrenceType(): void
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setRecurrenceType('unknown');

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this->buildViolation($constraint->choiceMessage)
            ->setParameters(['{{ allowed_values }}' => implode(', ', self::$expectedRecurrenceTypesValues)])
            ->setInvalidValue($recurrence->getRecurrenceType())
            ->atPath('recurrenceType')
            ->assertRaised();
    }

    public function testRecurrenceHasRequiredFieldsBlank(): void
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);

        $this->model->expects(self::once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn(['recurrenceType', 'interval', 'timeZone', 'startTime']);
        $this->model->expects(self::once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this
            ->buildViolation($constraint->notBlankMessage)->setInvalidValue(null)->atPath('interval')
            ->buildNextViolation($constraint->notBlankMessage)->setInvalidValue(null)->atPath('timeZone')
            ->buildNextViolation($constraint->notBlankMessage)->setInvalidValue(null)->atPath('startTime')
            ->assertRaised();
    }

    public function testRecurrenceHasTooBigInterval(): void
    {
        $actualInterval = 100;
        $maxInterval = 99;
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setInterval($actualInterval);

        $this->model->expects(self::once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);
        $this->model->expects(self::once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn($maxInterval);
        $this->model->expects(self::once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this->buildViolation($constraint->maxMessage)
            ->setParameters(['{{ limit }}' => $maxInterval])
            ->setInvalidValue($actualInterval)
            ->atPath('interval')
            ->assertRaised();
    }

    public function testRecurrenceHasTooSmallInterval(): void
    {
        $actualInterval = -1;
        $minInterval = 1;
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setInterval($actualInterval);

        $this->model->expects(self::once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);
        $this->model->expects(self::once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn($minInterval);
        $this->model->expects(self::once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this->buildViolation($constraint->minMessage)
            ->setParameters(['{{ limit }}' => $minInterval])
            ->setInvalidValue($actualInterval)
            ->atPath('interval')
            ->assertRaised();
    }

    public function testRecurrenceHasWrongMultipleOfInterval(): void
    {
        $actualInterval = 13;
        $intervalMultipleOf = 12;
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_YEARLY);
        $recurrence->setInterval($actualInterval);

        $this->model->expects(self::once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);
        $this->model->expects(self::once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn(999);
        $this->model->expects(self::once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn($intervalMultipleOf);

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this->buildViolation($constraint->multipleOfMessage)
            ->setParameters(['{{ multiple_of_value }}' => $intervalMultipleOf])
            ->setInvalidValue($actualInterval)
            ->atPath('interval')
            ->assertRaised();
    }

    public function testRecurrenceHasWrongEndTime(): void
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setStartTime(new \DateTime('2016-11-01 00:00:00', new \DateTimeZone('UTC')));
        $recurrence->setEndTime(new \DateTime('2016-10-01 00:00:00', new \DateTimeZone('UTC')));

        $formattedStartTime = '2016-11-01T00:00:00+00:00';

        $this->model->expects(self::once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);
        $this->model->expects(self::once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn(99);
        $this->model->expects(self::once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(1);

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this->buildViolation($constraint->minMessage)
            ->setParameters(['{{ limit }}' => $formattedStartTime])
            ->setInvalidValue($recurrence->getEndTime())
            ->atPath('endTime')
            ->assertRaised();
    }

    public function testRecurrenceHasWrongDayOfWeek(): void
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_WEEKLY);
        $recurrence->setDayOfWeek(['unknown']);

        $this->model->expects(self::once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);
        $this->model->expects(self::once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn(99);
        $this->model->expects(self::once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(1);

        $constraint = new Recurrence();
        $this->validator->validate($recurrence, $constraint);

        $this->buildViolation($constraint->multipleChoicesMessage)
            ->setParameters(['{{ allowed_values }}' => implode(', ', self::$expectedDaysOfWeekValues)])
            ->setInvalidValue($recurrence->getDayOfWeek())
            ->atPath('dayOfWeek')
            ->assertRaised();
    }
}
