<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class DailyStrategy extends AbstractStrategy
{
    /**
     * Maximum value of interval field for daily strategy is different.
     */
    const MAX_INTERVAL = 99;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_daily';
    }

    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $result = [];
        $occurrenceDate = $recurrence->getStartTime();
        $fromStartInterval = 1;
        if ($start > $occurrenceDate) {
            $dateInterval = $start->diff($occurrenceDate);
            $fromStartInterval = floor($dateInterval->format('%a') / $recurrence->getInterval());
            $occurrenceDate = $this->getNextOccurrence(
                $fromStartInterval++ * $recurrence->getInterval(),
                $occurrenceDate
            );
        }

        $occurrences = $recurrence->getOccurrences();
        while ($occurrenceDate <= $recurrence->getCalculatedEndTime()
            && $occurrenceDate <= $end
            && ($occurrences === null || $fromStartInterval <= $occurrences)
        ) {
            if ($occurrenceDate >= $start) {
                $result[] = $occurrenceDate;
            }
            $fromStartInterval++;
            $occurrenceDate = $this->getNextOccurrence($recurrence->getInterval(), $occurrenceDate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_DAILY;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = $recurrence->getInterval();

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.daily',
            ['%count%' => $interval]
        );
    }

    /**
     * Returns occurrence date according to last occurrence date and recurrence interval.
     *
     * @param integer $interval A number of days.
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($interval, \DateTime $date)
    {
        return new \DateTime("+{$interval} day {$date->format('c')}");
    }

    /**
     * {@inheritdoc}
     */
    public function getLastOccurrence(Entity\Recurrence $recurrence)
    {
        return $this->getNextOccurrence(
            $recurrence->getInterval() * ($recurrence->getOccurrences() - 1),
            $recurrence->getStartTime()
        );
    }
}
