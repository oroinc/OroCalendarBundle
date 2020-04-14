<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\CalendarBundle\Validator\Constraints\Recurrence;
use Oro\Bundle\CalendarBundle\Validator\RecurrenceValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecurrenceValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected static $expectedRecurrenceTypesValues = [
        Model\Recurrence::TYPE_DAILY,
        Model\Recurrence::TYPE_WEEKLY,
        Model\Recurrence::TYPE_MONTHLY,
        Model\Recurrence::TYPE_MONTH_N_TH,
        Model\Recurrence::TYPE_YEARLY,
        Model\Recurrence::TYPE_YEAR_N_TH,
    ];

    /**
     * @var array
     */
    protected static $expectedDaysOfWeekValues = [
        Model\Recurrence::DAY_SUNDAY,
        Model\Recurrence::DAY_MONDAY,
        Model\Recurrence::DAY_TUESDAY,
        Model\Recurrence::DAY_WEDNESDAY,
        Model\Recurrence::DAY_THURSDAY,
        Model\Recurrence::DAY_FRIDAY,
        Model\Recurrence::DAY_SATURDAY,
    ];

    /**
     * @var Recurrence
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $model;

    /**
     * @var RecurrenceValidator
     */
    protected $validator;

    protected function setUp(): void
    {
        $this->constraint = new Recurrence();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->model = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence')
            ->disableOriginalConstructor()
            ->getMock();
        $this->model->expects($this->any())
            ->method('getRecurrenceTypesValues')
            ->willReturn(self::$expectedRecurrenceTypesValues);
        $this->model->expects($this->any())
            ->method('getDaysOfWeekValues')
            ->willReturn(self::$expectedDaysOfWeekValues);

        $this->validator = new RecurrenceValidator($this->model);
        $this->validator->initialize($this->context);
    }

    public function testRecurrenceHasNoErrors()
    {
        $recurrence =  new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setStartTime(new \DateTime());
        $recurrence->setInterval(1);
        $recurrence->setTimeZone('UTC');

        $this->model->expects($this->once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn(['recurrenceType', 'interval', 'timeZone', 'startTime']);

        $this->model->expects($this->once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $this->context->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasBlankRecurrenceType()
    {
        $recurrence =  new Entity\Recurrence();

        $this->expectAddViolation(
            $this->at(0),
            'This value should not be blank.',
            [],
            null,
            'recurrenceType'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasWrongRecurrenceType()
    {
        $recurrence =  new Entity\Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setRecurrenceType('unknown');

        $this->expectAddViolation(
            $this->at(0),
            'This value should be one of the values: {{ allowed_values }}.',
            ['{{ allowed_values }}' => implode(', ', self::$expectedRecurrenceTypesValues)],
            $recurrence->getRecurrenceType(),
            'recurrenceType'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasRequiredFieldsBlank()
    {
        $recurrence =  new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);

        $this->model->expects($this->once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn(['recurrenceType', 'interval', 'timeZone', 'startTime']);

        $this->model->expects($this->once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $this->expectAddViolation(
            $this->at(0),
            'This value should not be blank.',
            [],
            null,
            'interval'
        );

        $this->expectAddViolation(
            $this->at(1),
            'This value should not be blank.',
            [],
            null,
            'timeZone'
        );

        $this->expectAddViolation(
            $this->at(2),
            'This value should not be blank.',
            [],
            null,
            'startTime'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasTooBigInterval()
    {
        $actualInterval = 100;
        $maxInterval = 99;
        $recurrence =  new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setInterval($actualInterval);

        $this->model->expects($this->once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);

        $this->model->expects($this->once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn($maxInterval);

        $this->model->expects($this->once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $this->expectAddViolation(
            $this->at(0),
            'This value should be {{ limit }} or less.',
            ['{{ limit }}' => $maxInterval],
            $actualInterval,
            'interval'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasTooSmallInterval()
    {
        $actualInterval = -1;
        $minInterval = RecurrenceValidator::MIN_INTERVAL;
        $recurrence =  new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setInterval($actualInterval);

        $this->model->expects($this->once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);

        $this->model->expects($this->once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn($minInterval);

        $this->model->expects($this->once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(0);

        $this->expectAddViolation(
            $this->at(0),
            'This value should be {{ limit }} or more.',
            ['{{ limit }}' => $minInterval],
            $actualInterval,
            'interval'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasWrongMultipleOfInterval()
    {
        $actualInterval = 13;
        $intervalMultipleOf = 12;
        $recurrence =  new Entity\Recurrence();
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_YEARLY);
        $recurrence->setInterval($actualInterval);

        $this->model->expects($this->once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);

        $this->model->expects($this->once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn(999);

        $this->model->expects($this->once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn($intervalMultipleOf);

        $this->expectAddViolation(
            $this->at(0),
            'This value should be a multiple of {{ multiple_of_value }}.',
            ['{{ multiple_of_value }}' => $intervalMultipleOf],
            $actualInterval,
            'interval'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasWrongEndTime()
    {
        $recurrence =  new Entity\Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_DAILY);
        $recurrence->setStartTime(new \DateTime('2016-11-01 00:00:00', new \DateTimeZone('UTC')));
        $recurrence->setEndTime(new \DateTime('2016-10-01 00:00:00', new \DateTimeZone('UTC')));

        $formattedStartTime = '2016-11-01T00:00:00+00:00';

        $this->model->expects($this->once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);

        $this->model->expects($this->once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn(99);

        $this->model->expects($this->once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(1);

        $this->expectAddViolation(
            $this->at(0),
            'This value should be {{ limit }} or more.',
            ['{{ limit }}' => $formattedStartTime],
            $recurrence->getEndTime(),
            'endTime'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    public function testRecurrenceHasWrongDayOfWeek()
    {
        $recurrence =  new Entity\Recurrence();
        $recurrence->setInterval(1);
        $recurrence->setRecurrenceType(Model\Recurrence::TYPE_WEEKLY);
        $recurrence->setDayOfWeek(['unknown']);

        $this->model->expects($this->once())
            ->method('getRequiredProperties')
            ->with($recurrence)
            ->willReturn([]);

        $this->model->expects($this->once())
            ->method('getMaxInterval')
            ->with($recurrence)
            ->willReturn(99);

        $this->model->expects($this->once())
            ->method('getIntervalMultipleOf')
            ->with($recurrence)
            ->willReturn(1);

        $this->expectAddViolation(
            $this->at(0),
            'One or more of the given values is not one of the values: {{ allowed_values }}.',
            ['{{ allowed_values }}' => implode(', ', self::$expectedDaysOfWeekValues)],
            $recurrence->getDayOfWeek(),
            'dayOfWeek'
        );

        $this->validator->validate($recurrence, $this->constraint);
    }

    /**
     * @param \PHPUnit\Framework\MockObject\Rule\InvocationOrder $matcher
     * @param string $message
     * @param array $parameters
     * @param mixed $invalidValue
     * @param string|null $path
     */
    protected function expectAddViolation(
        \PHPUnit\Framework\MockObject\Rule\InvocationOrder $matcher,
        $message,
        array $parameters,
        $invalidValue,
        $path
    ) {
        $builder = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');

        $this->context->expects($matcher)
            ->method('buildViolation')
            ->with($message, [])
            ->will($this->returnValue($builder));

        $builder->expects($this->once())
            ->method('setParameters')
            ->with($parameters)
            ->will($this->returnSelf());

        $builder->expects($this->once())
            ->method('setInvalidValue')
            ->with($invalidValue)
            ->will($this->returnSelf());

        if ($path) {
            $builder->expects($this->once())
                ->method('atPath')
                ->with($path)
                ->will($this->returnSelf());
        }

        $builder->expects($this->once())
            ->method('addViolation');
    }
}
