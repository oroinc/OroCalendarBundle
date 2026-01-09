<?php

namespace Oro\Bundle\CalendarBundle\Model;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;

/**
 * Model for managing calendar event recurrence patterns.
 *
 * Provides constants for recurrence types, instances, and days of week, and delegates
 * occurrence calculations to {@see StrategyInterface} implementations.
 */
class Recurrence
{
    /**
     * Used to calculate max endTime when it's empty and there are no occurrences specified.
     *
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\AbstractStrategy::getCalculatedEndTime
     */
    public const MAX_END_DATE = '9000-01-01T00:00:01+00:00';

    /**#@+
     * Type of recurrence
     *
     * Respective strategies:
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\DailyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\WeeklyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\MonthlyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\MonthNthStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\YearlyStrategy
     * @see \Oro\Bundle\CalendarBundle\Model\Recurrence\YearNthStrategy
     *
     * Property which obtains one of these values:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$recurrenceType
     */
    public const TYPE_DAILY = 'daily';
    public const TYPE_WEEKLY = 'weekly';
    public const TYPE_MONTHLY = 'monthly';
    public const TYPE_MONTH_N_TH = 'monthnth';
    public const TYPE_YEARLY = 'yearly';
    public const TYPE_YEAR_N_TH = 'yearnth';
    /**#@-*/

    /**#@+
     * It is used in monthnth and yearnth strategies, for creating recurring events like:
     * 'Yearly every 2 years on the first Saturday of April',
     * 'Monthly the fourth Saturday of every 2 months',
     * 'Yearly every 2 years on the last Saturday of April'.
     *
     * Property which obtains one of these values:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$instance
     */
    public const INSTANCE_FIRST = 1;
    public const INSTANCE_SECOND = 2;
    public const INSTANCE_THIRD = 3;
    public const INSTANCE_FOURTH = 4;
    public const INSTANCE_LAST = 5;
    /**#@-*/

    /**#@+
     * Constants of days used in recurrence.
     *
     * Property which obtains one of these values:
     * @see \Oro\Bundle\CalendarBundle\Entity\Recurrence::$dayOfWeek
     */
    public const DAY_SUNDAY = 'sunday';
    public const DAY_MONDAY = 'monday';
    public const DAY_TUESDAY = 'tuesday';
    public const DAY_WEDNESDAY = 'wednesday';
    public const DAY_THURSDAY = 'thursday';
    public const DAY_FRIDAY = 'friday';
    public const DAY_SATURDAY = 'saturday';
    /**#@-*/

    /** @var StrategyInterface  */
    protected $recurrenceStrategy;

    /** @var array */
    public static $instanceRelativeValues = [
        self::INSTANCE_FIRST => 'first',
        self::INSTANCE_SECOND => 'second',
        self::INSTANCE_THIRD => 'third',
        self::INSTANCE_FOURTH => 'fourth',
        self::INSTANCE_LAST => 'last',
    ];

    /** @var array */
    public static $weekdays = [
        self::DAY_MONDAY,
        self::DAY_TUESDAY,
        self::DAY_WEDNESDAY,
        self::DAY_THURSDAY,
        self::DAY_FRIDAY,
    ];

    /** @var array */
    public static $weekends = [
        self::DAY_SATURDAY,
        self::DAY_SUNDAY,
    ];

    public function __construct(StrategyInterface $recurrenceStrategy)
    {
        $this->recurrenceStrategy = $recurrenceStrategy;
    }

    /**
     * @param Entity\Recurrence $recurrence
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return \DateTime[]
     */
    public function getOccurrences(Entity\Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        return $this->recurrenceStrategy->getOccurrences($recurrence, $start, $end);
    }

    /**
     * @param Entity\Recurrence $recurrence
     *
     * @return string
     */
    public function getTextValue(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getTextValue($recurrence);
    }

    /**
     * @param Entity\Recurrence $recurrence
     *
     * @return \DateTime
     */
    public function getCalculatedEndTime(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getCalculatedEndTime($recurrence);
    }

    public function getMaxInterval(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getMaxInterval($recurrence);
    }

    public function getIntervalMultipleOf(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getIntervalMultipleOf($recurrence);
    }

    public function getRequiredProperties(Entity\Recurrence $recurrence)
    {
        return $this->recurrenceStrategy->getRequiredProperties($recurrence);
    }

    /**
     * Returns the list of possible values for recurrenceType.
     *
     * @return array
     */
    public function getRecurrenceTypesValues()
    {
        return array_values($this->getRecurrenceTypes());
    }

    /**
     * Returns the list of possible values for dayOfWeek.
     *
     * @return array
     */
    public function getDaysOfWeekValues()
    {
        return array_values($this->getDaysOfWeek());
    }

    /**
     * Returns the list of possible values(with labels) for recurrenceType.
     *
     * @return array
     */
    public function getRecurrenceTypes()
    {
        return [
            'oro.calendar.recurrence.types.daily' => self::TYPE_DAILY,
            'oro.calendar.recurrence.types.weekly' => self::TYPE_WEEKLY,
            'oro.calendar.recurrence.types.monthly' => self::TYPE_MONTHLY,
            'oro.calendar.recurrence.types.monthnth' => self::TYPE_MONTH_N_TH,
            'oro.calendar.recurrence.types.yearly' => self::TYPE_YEARLY,
            'oro.calendar.recurrence.types.yearnth' => self::TYPE_YEAR_N_TH,
        ];
    }

    /**
     * Returns the list of possible values(with labels) for instance.
     *
     * @return array
     */
    public function getInstances()
    {
        return [
            'oro.calendar.recurrence.instances.first' => self::INSTANCE_FIRST,
            'oro.calendar.recurrence.instances.second' => self::INSTANCE_SECOND,
            'oro.calendar.recurrence.instances.third' => self::INSTANCE_THIRD,
            'oro.calendar.recurrence.instances.fourth' => self::INSTANCE_FOURTH,
            'oro.calendar.recurrence.instances.last' => self::INSTANCE_LAST,
        ];
    }

    /**
     * Returns the list of possible values(with labels) for dayOfWeek.
     *
     * @return array
     */
    public function getDaysOfWeek()
    {
        return [
            'oro.calendar.recurrence.days.sunday' => self::DAY_SUNDAY,
            'oro.calendar.recurrence.days.monday' => self::DAY_MONDAY,
            'oro.calendar.recurrence.days.tuesday' => self::DAY_TUESDAY,
            'oro.calendar.recurrence.days.wednesday' => self::DAY_WEDNESDAY,
            'oro.calendar.recurrence.days.thursday' => self::DAY_THURSDAY,
            'oro.calendar.recurrence.days.friday' => self::DAY_FRIDAY,
            'oro.calendar.recurrence.days.saturday' => self::DAY_SATURDAY,
        ];
    }
}
