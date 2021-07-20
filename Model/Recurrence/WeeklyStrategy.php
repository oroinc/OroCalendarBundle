<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * The weekly strategy for the first week takes into account only that days that are later than start
 * of recurrence and then it selects next days according to its interval.
 * For example, the rule 'Weekly every 2 weeks on Monday, Friday every 2 weeks'
 * starting on Thursday, the 6th of October, 2016 will work in such way:
 *
 *  S   M   T   W   T   F   S
 *                          1
 *  2   3   4   5   6  [7]  8
 *  9  10  11  12  13  14  15
 * 16 [17] 18  19  20 [21] 22
 * 23  24  25  26  27  28  29
 * 30 [31]
 */
class WeeklyStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_weekly';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $result = [];
        $weekDays = $recurrence->getDayOfWeek();

        if (empty($weekDays)) {
            return $result;
        }

        //week days should be sorted in standard sequence (sun, mon, tue...)
        $this->sortWeekDays($weekDays);

        $startTimeNumericDay = $recurrence->getStartTime()->format('w');
        //move startTime to the first day of week
        $startTime = new \DateTime("-$startTimeNumericDay days {$recurrence->getStartTime()->format('c')}");
        /** @var float $fromStartInterval */
        $fromStartInterval = 0;
        $interval = $recurrence->getInterval();
        $fullWeeks = 0;
        //check if there were events before start of dates interval, so if yes we should calculate how many times
        if ($start > $startTime) {
            $dateInterval = $start->diff($startTime);

            //it calculates total occurrences were between $start and $startTime
            $fromStartInterval = floor(((int)$dateInterval->format('%a') + 1) / 7 / $interval) * count($weekDays);

            //fullWeeks must be calculated before recalculating fromStartInterval for first week, because it can
            //subtract the number of days equivalent to count of weekDays and one full week will be lost
            $fullWeeks = ceil($fromStartInterval / count($weekDays)) * $interval;
            foreach ($weekDays as $day) {
                $currentDay = new \DateTime($day, $this->getTimeZone());
                if ($currentDay->format('w') < $recurrence->getStartTime()->format('w')) {
                    //for the first week the items before startTime must not be taken into account
                    $fromStartInterval = $fromStartInterval == 0 ? $fromStartInterval : $fromStartInterval - 1;
                }
            }
        }

        $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");

        while ($afterFullWeeksDate <= $end && $afterFullWeeksDate <= $recurrence->getCalculatedEndTime()) {
            foreach ($weekDays as $day) {
                $next = $this->getNextOccurrence($day, $afterFullWeeksDate);
                if ($next > $end
                    || $next > $recurrence->getCalculatedEndTime()
                    || ($recurrence->getOccurrences() && $fromStartInterval >= $recurrence->getOccurrences())
                ) {
                    return $result;
                }

                if ($next >= $start
                    && $next <= $end
                    && $next >= $recurrence->getStartTime()
                    && $next <= $recurrence->getCalculatedEndTime()
                ) {
                    $result[] = $next;
                }

                $fromStartInterval = $next >= $recurrence->getStartTime() ? $fromStartInterval +1 : $fromStartInterval;
            }
            $fullWeeks += $interval;
            $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_WEEKLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = $recurrence->getInterval();
        $days = [];
        $dayOfWeek = $recurrence->getDayOfWeek();

        if ($this->getDayOfWeekRelativeValue($dayOfWeek) == 'weekday' && $interval == 1) {
            return $this->getFullRecurrencePattern(
                $recurrence,
                'oro.calendar.recurrence.patterns.weekday',
                ['%count%' => 0]
            );
        }

        foreach ($dayOfWeek as $day) {
            $days[] = $this->translator->trans('oro.calendar.recurrence.days.' . $day);
        }

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.weekly',
            ['%count%' => $interval, '%days%' => implode(', ', $days)]
        );
    }

    /**
     * Returns next date occurrence.
     *
     * @param string $day
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($day, \DateTime $date)
    {
        if (strtolower($date->format('l')) === strtolower($day)) {
            return $date;
        }

        return new \DateTime("next {$day} {$date->format('c')}");
    }

    /**
     * Sorts weekdays array to standard sequence(sun, mon, ...).
     *
     * @param $weekDays
     *
     * @return WeeklyStrategy
     */
    protected function sortWeekDays(&$weekDays)
    {
        usort($weekDays, function ($item1, $item2) {
            $date1 = new \DateTime($item1, $this->getTimeZone());
            $date2 = new \DateTime($item2, $this->getTimeZone());

            return $date1->format('w') <=> $date2->format('w');
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getLastOccurrence(Entity\Recurrence $recurrence)
    {
        $weekDays = $recurrence->getDayOfWeek();

        $this->sortWeekDays($weekDays);
        $firstDay = reset($weekDays);
        $currentDay = new \DateTime($firstDay, $this->getTimeZone());
        $startTime = $recurrence->getStartTime();
        if ($recurrence->getStartTime()->format('w') > $currentDay->format('w')) {
            $startTime = new \DateTime("previous $firstDay {$recurrence->getStartTime()->format('c')}");
        }

        $fullWeeks = (ceil($recurrence->getOccurrences() / count($weekDays)) - 1) * $recurrence->getInterval();
        $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");
        $fromStartInterval = $fullWeeks / $recurrence->getInterval() * count($weekDays);
        foreach ($weekDays as $day) {
            $currentDay = new \DateTime($day, $this->getTimeZone());
            if ($currentDay->format('w') < $recurrence->getStartTime()->format('w')) {
                $fromStartInterval = $fromStartInterval == 0 ? $fromStartInterval : $fromStartInterval - 1;
            }
        }

        if ($fromStartInterval + count($weekDays) < $recurrence->getOccurrences()) {
            $fullWeeks += $recurrence->getInterval();
            $afterFullWeeksDate = new \DateTime("+{$fullWeeks} week {$startTime->format('c')}");
            $fromStartInterval += count($weekDays);
        }

        //if no occurrences were at the first week, should skip that week
        if ($fromStartInterval == 0) {
            $afterFullWeeksDate = new \DateTime("+1 week {$startTime->format('c')}");
        }

        foreach ($weekDays as $day) {
            $next = $this->getNextOccurrence($day, $afterFullWeeksDate);
            $fromStartInterval = $next >= $recurrence->getStartTime() ? $fromStartInterval + 1 : $fromStartInterval;
            if ($fromStartInterval >= $recurrence->getOccurrences()) {
                return $next;
            }
        }

        return $recurrence->getStartTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredProperties(Entity\Recurrence $recurrence)
    {
        return array_merge(
            parent::getRequiredProperties($recurrence),
            ['dayOfWeek']
        );
    }
}
