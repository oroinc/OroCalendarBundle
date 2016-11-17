<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * Recurrence with type Recurrence::TYPE_YEARLY will provide interval a number of month, which is multiple of 12.
 */
class YearlyStrategy extends MonthlyStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_yearly';
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Entity\Recurrence $recurrence)
    {
        return $recurrence->getRecurrenceType() === Recurrence::TYPE_YEARLY;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        $interval = (int)($recurrence->getInterval() / 12);
        $currentDate = new \DateTime('now', $recurrence->getStartTime()->getTimezone());
        $currentDate->setDate($currentDate->format('Y'), $recurrence->getMonthOfYear(), $recurrence->getDayOfMonth());
        $date = $this->dateTimeFormatter->formatDay($currentDate);

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.yearly',
            $interval,
            ['%count%' => $interval, '%day%' => $date]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFirstOccurrence(Entity\Recurrence $recurrence)
    {
        $dayOfMonth = $recurrence->getDayOfMonth();
        $monthOfYear = $recurrence->getMonthOfYear();
        $interval = $recurrence->getInterval(); // a number of months, which is a multiple of 12
        $occurrenceDate = clone $recurrence->getStartTime();
        $occurrenceDate->setDate($occurrenceDate->format('Y'), $monthOfYear, $dayOfMonth);

        if ($occurrenceDate < $recurrence->getStartTime()) {
            $occurrenceDate = $this->getNextOccurrence($interval, $recurrence->getDayOfMonth(), $occurrenceDate);
        }

        return $occurrenceDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredProperties(Entity\Recurrence $recurrence)
    {
        return array_merge(
            parent::getRequiredProperties($recurrence),
            [
                'monthOfYear',
            ]
        );
    }

    /**
     * The multiplier for yearly recurrence type is 12, so only values of interval from this sequence are supported:
     * 12, 24, 36, ...
     *
     * {@inheritdoc}
     */
    public function getIntervalMultipleOf(Entity\Recurrence $recurrence)
    {
        return 12;
    }
}
