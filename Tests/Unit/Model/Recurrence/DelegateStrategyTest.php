<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\DelegateStrategy;

class DelegateStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var DelegateStrategy $strategy */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new DelegateStrategy();
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_delegate');
    }

    public function testAdd()
    {
        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $this->assertAttributeEquals(['bar' => $bar,'foo' => $foo], 'elements', $this->strategy);
    }

    public function testSupportsReturnTrue()
    {
        $recurrence = new Recurrence();
        $this->assertFalse($this->strategy->supports($recurrence));

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));

        $this->assertTrue($this->strategy->supports($recurrence));
    }

    public function testSupportsReturnFalse()
    {
        $recurrence = new Recurrence();
        $this->assertFalse($this->strategy->supports($recurrence));

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $this->assertFalse($this->strategy->supports($recurrence));
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
        $recurrence = $arguments[0];

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));

        $mocker = $bar->expects($this->once())
            ->method($method);

        call_user_func_array([$mocker, 'with'], $arguments);

        $mocker->willReturn($returnValue);

        $this->assertEquals($returnValue, call_user_func_array([$this->strategy, $method], $arguments));
    }

    /**
     * @return array
     */
    public function delegateMethodsDataProvider()
    {
        $recurrence = new Recurrence();
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

    /**
     * @param string $method
     * @param array $arguments
     *
     * @dataProvider delegateMethodsDataProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Recurrence type "baz" is not supported.
     */
    public function testDelegateMethodRaiseExceptionWhenStrategyNotMatched($method, array $arguments)
    {
        $recurrence = $arguments[0];
        $recurrence->setRecurrenceType('baz');

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $this->strategy->add($foo);
        $this->strategy->add($bar);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        call_user_func_array([$this->strategy, $method], $arguments);
    }

    /**
     * Creates mock object for StrategyInterface.
     *
     * @param string $name
     *
     * @return \Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createStrategy($name)
    {
        $result = $this->createMock('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface');
        $result->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        return $result;
    }
}
