<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element\EventRecurrence;

class Daily extends AbstractEventRecurrence
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        parent::setValue($value);

        if (!$this->isIntervalRecurrenceSet()) {
            $this->setNamedRecurrence($this->getIntervalRecurrenceValue());
        }
    }

    /**
     * Sets interval radio which make event trigger every weekday
     *
     * @param string $name
     */
    private function setNamedRecurrence($name)
    {
        $valueGroup = $this->findValueGroup($name);

        $radio = $valueGroup->find('css', "label span:contains($name)")
                    ->getParent()
                    ->find('css', 'input[type="radio"]');
        $radio->click();
    }
}
