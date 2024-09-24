<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Delegates a work to child recurrence strategies.
 */
class DelegateStrategy implements StrategyInterface, ResetInterface
{
    /** @var iterable|StrategyInterface[] */
    private $strategies;

    /** @var StrategyInterface[] */
    private $initializedStrategies;

    /**
     * @param iterable|StrategyInterface[] $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->strategies = $strategies;
    }

    #[\Override]
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end)
    {
        return $this->getStrategy($recurrence)->getOccurrences($recurrence, $start, $end);
    }

    #[\Override]
    public function supports(Recurrence $recurrence)
    {
        return null !== $this->findStrategy($recurrence);
    }

    #[\Override]
    public function getTextValue(Recurrence $recurrence)
    {
        return $this->getStrategy($recurrence)->getTextValue($recurrence);
    }

    #[\Override]
    public function getCalculatedEndTime(Recurrence $recurrence)
    {
        return $this->getStrategy($recurrence)->getCalculatedEndTime($recurrence);
    }

    #[\Override]
    public function getMaxInterval(Recurrence $recurrence)
    {
        return $this->getStrategy($recurrence)->getMaxInterval($recurrence);
    }

    #[\Override]
    public function getIntervalMultipleOf(Recurrence $recurrence)
    {
        return $this->getStrategy($recurrence)->getIntervalMultipleOf($recurrence);
    }

    #[\Override]
    public function getRequiredProperties(Recurrence $recurrence)
    {
        return $this->getStrategy($recurrence)->getRequiredProperties($recurrence);
    }

    #[\Override]
    public function getName()
    {
        return 'recurrence_delegate';
    }

    #[\Override]
    public function reset()
    {
        $this->initializedStrategies = null;
    }

    private function findStrategy(Recurrence $recurrence): ?StrategyInterface
    {
        $strategies = $this->getStrategies();
        foreach ($strategies as $strategy) {
            if ($strategy->supports($recurrence)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * @throws \InvalidArgumentException if a strategy was not found
     */
    private function getStrategy(Recurrence $recurrence): StrategyInterface
    {
        $strategy = $this->findStrategy($recurrence);
        if (null === $strategy) {
            throw new \InvalidArgumentException(sprintf(
                'Recurrence type "%s" is not supported.',
                $recurrence->getRecurrenceType()
            ));
        }

        return $strategy;
    }

    /**
     * @return StrategyInterface[] [name => strategy, ...]
     */
    private function getStrategies(): array
    {
        if (null === $this->initializedStrategies) {
            $this->initializedStrategies = [];
            foreach ($this->strategies as $strategy) {
                $this->initializedStrategies[$strategy->getName()] = $strategy;
            }
        }

        return $this->initializedStrategies;
    }
}
