<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurrenceTest extends \PHPUnit_Framework_TestCase
{
    /** @var Recurrence */
    protected $model;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface')
            ->getMock();

        $this->model = new Recurrence($this->strategy);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @param mixed $returnValue
     *
     * @dataProvider delegateMethodsDataProvider
     */
    public function testDelegateMethodWorks($method, array $arguments, $returnValue)
    {
        $mocker = $this->strategy->expects($this->once())
            ->method($method);

        call_user_func_array([$mocker, 'with'], $arguments);

        $mocker->willReturn($returnValue);

        $this->assertEquals($returnValue, call_user_func_array([$this->model, $method], $arguments));
    }

    /**
     * @return array
     */
    public function delegateMethodsDataProvider()
    {
        $recurrence = new Entity\Recurrence();
        $start = new \DateTime('2016-10-10 10:00:00');
        $end = new \DateTime('2016-11-10 10:00:00');

        return [
            'getTextValue' => [
                'method' => 'getTextValue',
                'arguments' => [$recurrence],
                'returnValue' => 'foo',
            ],
            'getCalculatedEndTime' => [
                'method' => 'getCalculatedEndTime',
                'arguments' => [$recurrence],
                'returnValue' => new \DateTime(),
            ],
            'getMaxInterval' => [
                'method' => 'getMaxInterval',
                'arguments' => [$recurrence],
                'returnValue' => 1,
            ],
            'getIntervalMultipleOf' => [
                'method' => 'getIntervalMultipleOf',
                'arguments' => [$recurrence],
                'returnValue' => 1,
            ],
            'getRequiredProperties' => [
                'method' => 'getRequiredProperties',
                'arguments' => [$recurrence],
                'returnValue' => ['interval', 'startTime', 'timeZone'],
            ],
            'getOccurrences' => [
                'method' => 'getOccurrences',
                'arguments' => [$recurrence, $start, $end],
                'returnValue' => [new \DateTime()],
            ],
        ];
    }

    public function testGetRecurrenceTypesValues()
    {
        $this->assertEquals(
            [
                Recurrence::TYPE_DAILY,
                Recurrence::TYPE_WEEKLY,
                Recurrence::TYPE_MONTHLY,
                Recurrence::TYPE_MONTH_N_TH,
                Recurrence::TYPE_YEARLY,
                Recurrence::TYPE_YEAR_N_TH
            ],
            $this->model->getRecurrenceTypesValues()
        );
    }

    public function testGetDaysOfWeekValues()
    {
        $this->assertEquals(
            [
                Recurrence::DAY_SUNDAY,
                Recurrence::DAY_MONDAY,
                Recurrence::DAY_TUESDAY,
                Recurrence::DAY_WEDNESDAY,
                Recurrence::DAY_THURSDAY,
                Recurrence::DAY_FRIDAY,
                Recurrence::DAY_SATURDAY,
            ],
            $this->model->getDaysOfWeekValues()
        );
    }

    public function testGetRecurrenceTypes()
    {
        $this->assertEquals(
            [
                Recurrence::TYPE_DAILY => 'oro.calendar.recurrence.types.daily',
                Recurrence::TYPE_WEEKLY => 'oro.calendar.recurrence.types.weekly',
                Recurrence::TYPE_MONTHLY => 'oro.calendar.recurrence.types.monthly',
                Recurrence::TYPE_MONTH_N_TH => 'oro.calendar.recurrence.types.monthnth',
                Recurrence::TYPE_YEARLY => 'oro.calendar.recurrence.types.yearly',
                Recurrence::TYPE_YEAR_N_TH => 'oro.calendar.recurrence.types.yearnth',
            ],
            $this->model->getRecurrenceTypes()
        );
    }

    public function testGetInstances()
    {
        $this->assertEquals(
            [
                Recurrence::INSTANCE_FIRST => 'oro.calendar.recurrence.instances.first',
                Recurrence::INSTANCE_SECOND => 'oro.calendar.recurrence.instances.second',
                Recurrence::INSTANCE_THIRD => 'oro.calendar.recurrence.instances.third',
                Recurrence::INSTANCE_FOURTH => 'oro.calendar.recurrence.instances.fourth',
                Recurrence::INSTANCE_LAST => 'oro.calendar.recurrence.instances.last',
            ],
            $this->model->getInstances()
        );
    }

    public function testGetDaysOfWeek()
    {
        $this->assertEquals(
            [
                Recurrence::DAY_SUNDAY => 'oro.calendar.recurrence.days.sunday',
                Recurrence::DAY_MONDAY => 'oro.calendar.recurrence.days.monday',
                Recurrence::DAY_TUESDAY => 'oro.calendar.recurrence.days.tuesday',
                Recurrence::DAY_WEDNESDAY => 'oro.calendar.recurrence.days.wednesday',
                Recurrence::DAY_THURSDAY => 'oro.calendar.recurrence.days.thursday',
                Recurrence::DAY_FRIDAY => 'oro.calendar.recurrence.days.friday',
                Recurrence::DAY_SATURDAY => 'oro.calendar.recurrence.days.saturday',
            ],
            $this->model->getDaysOfWeek()
        );
    }
}
