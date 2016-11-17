<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class DelegateStrategy implements StrategyInterface
{
    /** @var StrategyInterface[] */
    protected $elements = [];

    /**
     * Adds recurrence strategy.
     *
     * @param StrategyInterface $strategy
     *
     * @return DelegateStrategy
     */
    public function add(StrategyInterface $strategy)
    {
        $this->elements[$strategy->getName()] = $strategy;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        $delegate = $this->match($recurrence, true);

        return $delegate->getOccurrences($recurrence, $start, $end);
    }

    /**
     * Checks if strategy can be used and returns its instance.
     *
     * @param Recurrence $recurrence
     * @param bool $required
     *
     * @return StrategyInterface|null
     * @throws \InvalidArgumentException
     */
    protected function match(Recurrence $recurrence, $required = false)
    {
        foreach ($this->elements as $strategy) {
            /** @var StrategyInterface $strategy */
            if ($strategy->supports($recurrence)) {
                return $strategy;
            }
        }

        if ($required) {
            throw new \InvalidArgumentException(
                sprintf('Recurrence type "%s" is not supported.', $recurrence->getRecurrenceType())
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Recurrence $recurrence)
    {
        return $this->match($recurrence) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextValue(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence, true);

        return $delegate->getTextValue($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getCalculatedEndTime(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence, true);

        return $delegate->getCalculatedEndTime($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxInterval(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence, true);

        return $delegate->getMaxInterval($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getIntervalMultipleOf(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence, true);

        return $delegate->getIntervalMultipleOf($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredProperties(Recurrence $recurrence)
    {
        $delegate = $this->match($recurrence, true);

        return $delegate->getRequiredProperties($recurrence);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recurrence_delegate';
    }
}
