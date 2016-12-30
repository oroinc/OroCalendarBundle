<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class MonthlyStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_monthly';
    }

    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $result = [];
        $occurrenceDate = $this->getFirstOccurrence($recurrence);
        $interval = $recurrence->getInterval();
        $fromStartInterval = 1;

        if ($start > $occurrenceDate) {
            $dateInterval = $start->diff($occurrenceDate);
            $fromStartInterval = (int)$dateInterval->format('%y') * 12 + (int)$dateInterval->format('m');
            $fromStartInterval = floor($fromStartInterval / $interval);
            $occurrenceDate = $this->getNextOccurrence($fromStartInterval++ * $interval, $recurrence, $occurrenceDate);
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
            $occurrenceDate = $this->getNextOccurrence($interval, $recurrence, $occurrenceDate);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_MONTHLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = $recurrence->getInterval();

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.monthly',
            $interval,
            ['%count%' => $interval, '%day%' => $recurrence->getDayOfMonth()]
        );
    }

    /**
     * Returns occurrence date according to last occurrence date and recurrence interval.
     *
     * @param integer $interval A number of months.
     * @param Entity\Recurrence $recurrence
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    protected function getNextOccurrence($interval, Entity\Recurrence $recurrence, \DateTime $date)
    {
        $currentDate = clone $date;
        $currentDate->setDate($currentDate->format('Y'), $currentDate->format('m'), 1);

        $result = new \DateTime("+{$interval} month {$currentDate->format('c')}");
        $result->setDate(
            $result->format('Y'),
            $result->format('m'),
            $this->getAdjustedDayOfMonth($recurrence, $result)
        );

        return $result;
    }

    /**
     * Returns first occurrence according to recurrence rules.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return \DateTime
     */
    protected function getFirstOccurrence(Entity\Recurrence $recurrence)
    {
        $occurrenceDate = clone $recurrence->getStartTime();
        $occurrenceDate->setDate(
            $occurrenceDate->format('Y'),
            $occurrenceDate->format('m'),
            $this->getAdjustedDayOfMonth($recurrence, $occurrenceDate)
        );

        if ($occurrenceDate->format('d') < $recurrence->getStartTime()->format('d')) {
            $occurrenceDate = $this->getNextOccurrence(
                $recurrence->getInterval(),
                $recurrence,
                $occurrenceDate
            );
        }

        return $occurrenceDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastOccurrence(Entity\Recurrence $recurrence)
    {
        $occurrenceDate = $this->getFirstOccurrence($recurrence);

        return $this->getNextOccurrence(
            ($recurrence->getOccurrences() - 1) * $recurrence->getInterval(),
            $recurrence,
            $occurrenceDate
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredProperties(Entity\Recurrence $recurrence)
    {
        return array_merge(
            parent::getRequiredProperties($recurrence),
            ['dayOfMonth']
        );
    }

    /**
     * This method aimed to correct/adjust $dayOfMonth.
     * For example, if event is repeated by pattern "Monthly day 31 of every 1 month",
     * then for January $dayOfMonth=31; for February $dayOfMonth=29 at leap year
     * and for February $dayOfMonth=28 at usual year; for April $dayOfMonth=30 and so on.
     *
     * @param Entity\Recurrence $recurrence
     * @param \DateTime $occurrenceDate
     *
     * @return int|null
     */
    protected function getAdjustedDayOfMonth(Entity\Recurrence $recurrence, \DateTime $occurrenceDate)
    {
        $daysInMonth = (int)$occurrenceDate->format('t');

        return ($recurrence->getDayOfMonth() > $daysInMonth) ? $daysInMonth : $recurrence->getDayOfMonth();
    }
}
