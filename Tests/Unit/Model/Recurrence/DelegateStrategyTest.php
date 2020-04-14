<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\DelegateStrategy;
use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;

class DelegateStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testGetName()
    {
        $strategy = new DelegateStrategy([]);
        $this->assertEquals($strategy->getName(), 'recurrence_delegate');
    }

    public function testSupportsWhenExistsStrategyThatSupportRecurrence()
    {
        $recurrence = new Recurrence();

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $strategy = new DelegateStrategy([$foo, $bar]);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(true));

        $this->assertTrue($strategy->supports($recurrence));
    }

    public function testSupportsWhenNoStrategyThatSupportRecurrence()
    {
        $recurrence = new Recurrence();

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $strategy = new DelegateStrategy([$foo, $bar]);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $this->assertFalse($strategy->supports($recurrence));
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
        $strategy = new DelegateStrategy([$foo, $bar]);

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

        $this->assertEquals($returnValue, call_user_func_array([$strategy, $method], $arguments));
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
     */
    public function testDelegateMethodRaiseExceptionWhenStrategyNotMatched($method, array $arguments)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recurrence type "baz" is not supported.');

        $recurrence = $arguments[0];
        $recurrence->setRecurrenceType('baz');

        $foo = $this->createStrategy('foo');
        $bar = $this->createStrategy('bar');
        $strategy = new DelegateStrategy([$foo, $bar]);

        $foo->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($recurrence)
            ->will($this->returnValue(false));

        call_user_func_array([$strategy, $method], $arguments);
    }

    /**
     * @param string $name
     *
     * @return StrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createStrategy($name)
    {
        $result = $this->createMock(StrategyInterface::class);
        $result->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        return $result;
    }
}
