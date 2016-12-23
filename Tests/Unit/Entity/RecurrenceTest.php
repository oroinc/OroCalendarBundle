<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class RecurrenceTest extends AbstractEntityTest
{
    /**
     * {@inheritDoc}
     */
    public function getEntityFQCN()
    {
        return 'Oro\Bundle\CalendarBundle\Entity\Recurrence';
    }

    /**
     * {@inheritDoc}
     */
    public function getSetDataProvider()
    {
        return [
            ['recurrence_type', 'daily', 'daily'],
            ['interval', 99, 99],
            ['instance', 3, 3],
            ['day_of_week', ['monday', 'wednesday'], ['monday', 'wednesday']],
            ['day_of_month', 28, 28],
            ['month_of_year', 8, 8],
            ['start_time', $start = new \DateTime(), $start],
            ['end_time', $end = new \DateTime(), $end],
            ['calculated_end_time', $cet = new \DateTime(), $cet],
            ['calendar_event', new CalendarEvent(), new CalendarEvent()],
            ['occurrences', 1, 1],
        ];
    }

    /**
     * @dataProvider dayOfMonthDataProvider
     *
     * @param int $dayOfMonth
     * @param string $date
     * @param int $expectedDayOfMonth
     */
    public function testGetAdjustedDayOfMonth($dayOfMonth, $date, $expectedDayOfMonth)
    {
        $entity = new Recurrence();
        $entity->setDayOfMonth($dayOfMonth);
        $occurrenceDate = new \DateTime($date);

        $this->assertEquals($expectedDayOfMonth, $entity->getAdjustedDayOfMonth($occurrenceDate));
    }

    public function dayOfMonthDataProvider()
    {
        return [
            'dayOfMonth greater than days in month in usual January'    => [31, '2016-01-01', 31],
            'dayOfMonth greater than days in month in leap February'    => [31, '2016-02-01', 29],
            'dayOfMonth greater than days in month in usual February'   => [31, '2017-02-01', 28],
            'dayOfMonth greater than days in month in March'            => [31, '2017-03-01', 31],
            'dayOfMonth greater than days in month in April'            => [31, '2017-04-01', 30],
            'dayOfMonth greater than days in month in november'         => [31, '2016-11-01', 30],
            'dayOfMonth less than days in month'                        => [15, '2016-11-01', 15],
        ];
    }
}
