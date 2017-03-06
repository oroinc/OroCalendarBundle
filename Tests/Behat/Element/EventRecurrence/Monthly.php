<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element\EventRecurrence;

class Monthly extends AbstractEventRecurrence
{
    public function setValue($value)
    {
        parent::setValue($value);

        $this->setRepeatOnCombination($this->getRecurrenceParams(self::REPEAT_ON_KEY));
    }

    /**
     * Set combination of recurrence selects
     * Example: choose selects Repeat On First, Monday
     *
     * @param string $repeatOn
     */
    private function setRepeatOnCombination($repeatOn)
    {
        $matches = [];
        preg_match('/(?P<type>\w+) (?P<value>\w+)/', $repeatOn, $matches);

        $message = "Bad recurrence format. Example: 'Repeat on:First Sunday' or 'Repeat on:Day 10'";
        self::assertCount(5, $matches, $message);
        self::assertArrayHasKey('type', $matches, $message);
        self::assertArrayHasKey('value', $matches, $message);

        $typeSelect = $this->find('css', '.selector select');
        self::assertNotNull($typeSelect, "Recurrence type select not found (.selector select)");

        $typeSelect->selectOption($matches['type']);

        if ('Day' === $matches['type']) {
            $dayInput = $this->find('css', 'input[data-related-field="dayOfMonth"]');
            self::assertNotNull(
                $typeSelect,
                "Recurrence days input not found (input[data-related-field='dayOfMonth'])"
            );

            // TODO: here must be some js triggers, because just setValue not accepted
            $dayInput->setValue($matches['value']);
        } else {
            $repeatOnSelect = $this->find('css', 'span[data-name="repeat-on-instance"] select');
            self::assertNotNull(
                $repeatOnSelect,
                "Recurrence select not found (span[data-name='repeat-on-instance'] select)"
            );
            $repeatOnSelect->selectOption($matches['value']);
        }
    }
}
