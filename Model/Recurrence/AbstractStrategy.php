<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide default functionality for calendar strategies
 */
abstract class AbstractStrategy implements StrategyInterface
{
    /**
     * Maximum value of interval field.
     */
    const MAX_INTERVAL = 999;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DateTimeFormatterInterface */
    protected $dateTimeFormatter;

    /** @var \DateTimeZone */
    protected $timeZone;

    /** @var LocaleSettings */
    protected $localeSettings;

    /**
     * AbstractStrategy constructor.
     */
    public function __construct(
        TranslatorInterface $translator,
        DateTimeFormatterInterface $formatter,
        LocaleSettings $localeSettings
    ) {
        $this->translator = $translator;
        $this->dateTimeFormatter = $formatter;
        $this->localeSettings = $localeSettings;
    }

    /**
     * Returns occurrences text pattern, if it is applicable for recurrence.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getOccurrencesPattern(Entity\Recurrence $recurrence)
    {
        $occurrences = $recurrence->getOccurrences();
        $result = '';
        if ($occurrences > 0) {
            $result = $this->translator->trans(
                'oro.calendar.recurrence.patterns.occurrences',
                ['%count%' => $occurrences]
            );
        }

        return $result;
    }

    /**
     * Returns end date text pattern, if it is applicable for recurrence.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getEndDatePattern(Entity\Recurrence $recurrence)
    {
        $result = '';
        if ($recurrence->getEndTime() !== null) {
            $result = $this->translator->trans(
                'oro.calendar.recurrence.patterns.end_date',
                ['%date%' => $this->dateTimeFormatter->formatDate($recurrence->getEndTime())]
            );
        }

        return $result;
    }

    /**
     * Returns recurrence pattern text according to its translation and parameters.
     *
     * @param Entity\Recurrence $recurrence
     * @param string $translationId
     * @param array $translationParameters
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getFullRecurrencePattern(
        Entity\Recurrence $recurrence,
        string $translationId,
        array $translationParameters
    ) {
        $translationParameters['%occurrences%'] = $this->getOccurrencesPattern($recurrence);
        $translationParameters['%end_date%'] = $this->getEndDatePattern($recurrence);
        $translationParameters['%timezone_info%'] = $this->getTimezoneInfo($recurrence);

        return $this->translator->trans(
            $translationId,
            $translationParameters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCalculatedEndTime(Entity\Recurrence $recurrence)
    {
        $occurrences = $recurrence->getOccurrences();
        $currentEndTime = $recurrence->getEndTime();

        if (!empty($currentEndTime)) {
            $result = $currentEndTime;
        } elseif (!empty($occurrences)) {
            $recurrenceTimezone = new \DateTimeZone($recurrence->getTimeZone());
            $recurrence->getStartTime()->setTimezone($recurrenceTimezone);
            $result = $this->getLastOccurrence($recurrence);
            $result->setTimezone(new \DateTimeZone('UTC'));
        } else {
            $result = new \DateTime(Recurrence::MAX_END_DATE, $this->getTimeZone());
        }

        return $result;
    }

    /**
     * Returns relative value for dayOfWeek of recurrence entity.
     * It is used for generating textual representation
     * of recurrences like:
     * 'Yearly every 2 years on the first weekday of April',
     * 'Monthly the fourth weekend of every 2 months' etc.
     * In other words it returns textual representation of:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$dayOfWeek
     *
     * Possible relative values:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$dayOfWeek
     *
     * @param array $dayOfWeek
     *
     * @return string
     */
    public function getDayOfWeekRelativeValue(array $dayOfWeek)
    {
        sort($dayOfWeek);
        sort(Recurrence::$weekends);
        if (Recurrence::$weekends == $dayOfWeek) {
            return 'weekend';
        }

        sort(Recurrence::$weekdays);
        if (Recurrence::$weekdays == $dayOfWeek) {
            return 'weekday';
        }

        if (count($dayOfWeek) == 7) {
            return 'day';
        }

        //returns first element
        return reset($dayOfWeek);
    }

    /**
     * Returns recurrence instance relative value by its key.
     *
     * @param $key
     *
     * @return null|string
     */
    public function getInstanceRelativeValue($key)
    {
        return empty(Recurrence::$instanceRelativeValues[$key]) ? null : Recurrence::$instanceRelativeValues[$key];
    }

    /**
     * Returns last occurrence date according occurrences value.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return \DateTime
     */
    abstract public function getLastOccurrence(Entity\Recurrence $recurrence);

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

    /**
     * Returns time zone info, if the date in time zone that was used for recurring event creation differs
     * from date in time zone that currently is used by Oro system.
     *
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getTimezoneInfo(Entity\Recurrence $recurrence)
    {
        $result = '';

        if ($recurrence->getCalendarEvent() && $recurrence->getCalendarEvent()->getStart() !== null) {
            $originalStart = clone $recurrence->getCalendarEvent()->getStart();
            $originalStart->setTimezone(new \DateTimeZone($recurrence->getTimeZone()));
            $currentStart = clone $recurrence->getCalendarEvent()->getStart();
            $currentStart->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
            if ($originalStart->format('Y-m-d') != $currentStart->format('Y-m-d')) {
                $result = $this->translator->trans(
                    'oro.calendar.recurrence.patterns.timezone',
                    ['%timezone%' => $recurrence->getTimeZone(), '%timezone_offset%' => $originalStart->format('P')]
                );
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxInterval(Entity\Recurrence $recurrence)
    {
        return static::MAX_INTERVAL;
    }

    /**
     * By default the multiplier is NULL so any value of interval is supported.
     *
     * {@inheritdoc}
     */
    public function getIntervalMultipleOf(Entity\Recurrence $recurrence)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredProperties(Entity\Recurrence $recurrence)
    {
        return [
            'recurrenceType',
            'interval',
            'startTime',
            'timeZone',
        ];
    }
}
