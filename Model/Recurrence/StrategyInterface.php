<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

interface StrategyInterface
{
    /**
     * Calculates occurrences dates according to recurrence rules and dates interval.
     *
     * @param Recurrence $recurrence
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return \DateTime[]
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end);

    /**
     * Checks if strategy supports recurrence type.
     *
     * @param Recurrence $recurrence
     *
     * @return bool
     */
    public function supports(Recurrence $recurrence);

    /**
     * Get name of recurrence strategy.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns textual representation of recurrence rules.
     *
     * @param Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getTextValue(Recurrence $recurrence);

    /**
     * Calculates and returns last occurrence date.
     *
     * @param Recurrence $recurrence
     *
     * @return \DateTime
     */
    public function getCalculatedEndTime(Recurrence $recurrence);

    /**
     * Returns maximum interval for this recurrence strategy.
     *
     * @param Recurrence $recurrence
     *
     * @return integer
     */
    public function getMaxInterval(Recurrence $recurrence);

    /**
     * Get multiple of interval. For example if recurrence type is "yearly" the interval has to be a multiple of 12.
     * It means only next values of the interval are supported: 12, 24, 36, ...
     *
     * @param Recurrence $recurrence
     *
     * @return integer
     */
    public function getIntervalMultipleOf(Recurrence $recurrence);

    /**
     * Returns list of required fields for this recurrence strategy.
     *
     * @param Recurrence $recurrence
     *
     * @return array
     */
    public function getRequiredProperties(Recurrence $recurrence);
}
