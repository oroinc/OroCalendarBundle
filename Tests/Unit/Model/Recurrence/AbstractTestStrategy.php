<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;

abstract class AbstractTestStrategy extends \PHPUnit\Framework\TestCase
{
    /** @var \Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface */
    protected $strategy;

    /** @var \DateTimeZone */
    protected $timeZone;

    /**
     * @return array
     */
    abstract public function propertiesDataProvider();

    /**
     * @return string
     */
    abstract protected function getType();

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @dataProvider propertiesDataProvider
     */
    public function testGetOccurrences(array $params, array $expected)
    {
        $timeZone = $this->getTimeZone();
        $expected = array_map(
            function ($date) use ($timeZone) {
                return new \DateTime($date, $timeZone);
            },
            $expected
        );
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType($this->getType());
        if (!empty($params['startTime'])) {
            $recurrence->setStartTime(new \DateTime($params['startTime'], $timeZone));
        }
        if (!empty($params['endTime'])) {
            $recurrence->setEndTime(new \DateTime($params['endTime'], $timeZone))
                ->setCalculatedEndTime(new \DateTime($params['endTime'], $timeZone));
        } else {
            $recurrence->setCalculatedEndTime($this->strategy->getCalculatedEndTime($recurrence));
        }
        if (!empty($params['interval'])) {
            $recurrence->setInterval($params['interval']);
        }
        if (!empty($params['instance'])) {
            $recurrence->setInstance($params['instance']);
        }
        if (!empty($params['occurrences'])) {
            $recurrence->setOccurrences($params['occurrences']);
        }
        if (!empty($params['daysOfWeek'])) {
            $recurrence->setDayOfWeek($params['daysOfWeek']);
        }
        if (!empty($params['dayOfMonth'])) {
            $recurrence->setDayOfMonth($params['dayOfMonth']);
        }
        if (!empty($params['monthOfYear'])) {
            $recurrence->setMonthOfYear($params['monthOfYear']);
        }
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime($params['start'], $timeZone),
            new \DateTime($params['end'], $timeZone)
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @return \DateTimeZone
     */
    protected function getTimeZone()
    {
        if ($this->timeZone === null) {
            $this->timeZone = new \DateTimeZone('UTC');
        }

        return $this->timeZone;
    }
}
