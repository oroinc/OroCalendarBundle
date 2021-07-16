<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element\EventRecurrence;

class Weekly extends AbstractEventRecurrence
{
    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        parent::setValue($values);

        if (key_exists(self::REPEAT_ON_KEY, $this->getRecurrenceParams())) {
            $this->setRepeatOnDay($this->getRecurrenceParams(self::REPEAT_ON_KEY));
        }
    }

    /**
     * Set event repeating on specific day of week
     *
     * @param string $day
     */
    private function setRepeatOnDay($day)
    {
        $valueGroup = $this->findValueGroup(self::REPEAT_ON_KEY);

        $checkbox = $valueGroup->find('css', "input[value='$day']");
        self::assertNotNull($checkbox, "$day checkbox not found, selector: (input[value='$day'])");
        $checkbox->click();
    }
}
