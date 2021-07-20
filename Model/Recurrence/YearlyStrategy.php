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
        $startYear = $recurrence->getStartTime() ? $recurrence->getStartTime()->format('Y') : null;
        $interval = (int)($recurrence->getInterval() / 12);
        $recurrenceStartDate = $recurrence->getStartTime();
        $anyDateWithMonthRecurrence = (new \DateTime())->setTimestamp(mktime(
            0,
            0,
            0,
            $recurrence->getMonthOfYear(),
            $this->getRecurrenceDay($recurrence->getMonthOfYear(), $startYear),
            $startYear
        ));
        // Some monthly patterns are equivalent to yearly patterns.
        // In these cases, day should be adjusted to fit last day of month.
        // For example "Monthly day 31 of every 12 months, start Wed 11/30/2016" === "Yearly every 1 year on Nov 30".
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))
            ->setDate(
                $recurrenceStartDate->format('Y'),
                $recurrence->getMonthOfYear(),
                $this->getDayOfMonthInValidRange($recurrence, $anyDateWithMonthRecurrence)
            );

        $formattedDay = $this->dateTimeFormatter->formatDay($date, null, null, 'UTC');

        return $this->getFullRecurrencePattern(
            $recurrence,
            'oro.calendar.recurrence.patterns.yearly',
            ['%count%' => $interval, '%day%' => $formattedDay]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFirstOccurrence(Entity\Recurrence $recurrence)
    {
        $startYear = $recurrence->getStartTime() ? $recurrence->getStartTime()->format('Y') : null;
        $anyDateWithMonthRecurrence = (new \DateTime())->setTimestamp(mktime(
            0,
            0,
            0,
            $recurrence->getMonthOfYear(),
            $this->getRecurrenceDay($recurrence->getMonthOfYear(), $startYear),
            $startYear
        ));
        $monthOfYear = $recurrence->getMonthOfYear();
        $interval = $recurrence->getInterval(); // a number of months, which is a multiple of 12
        $occurrenceDate = clone $recurrence->getStartTime();
        $occurrenceDate->setDate(
            $occurrenceDate->format('Y'),
            $monthOfYear,
            $this->getDayOfMonthInValidRange($recurrence, $anyDateWithMonthRecurrence)
        );

        if ($occurrenceDate < $recurrence->getStartTime()) {
            $occurrenceDate = $this->getNextOccurrence($interval, $recurrence, $occurrenceDate);
        }

        return $occurrenceDate;
    }

    private function getRecurrenceDay(int $month, ?int $year): int
    {
        $date = new \DateTime(sprintf('%d-%d-1', $year ?: date('Y'), $month));

        $lastDay = date('t', $date->getTimestamp());
        $currentDay = date('d');

        return $currentDay > $lastDay ? $lastDay : $currentDay;
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
