<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element\EventRecurrence;

class Yearly extends AbstractEventRecurrence
{
    public function setValue($value)
    {
        parent::setValue($value);

        $this->setRepeatOnCombination($this->getRecurrenceParams(self::REPEAT_ON_KEY));
    }

    /**
     * Set combination of recurrence selects
     * Example: choose selects Repeat On May, Second, Weekday
     *
     * @param string $repeatOn
     */
    private function setRepeatOnCombination($repeatOn)
    {
        $matches = [];
        preg_match('/(?P<month>\w+) (?P<value>\w+) (?P<dayOfWeek>\w+)/', $repeatOn, $matches);

        $message = "Bad recurrence format. Example: 'Repeat on:First Sunday' or 'Repeat on:Day 10'";
        self::assertCount(7, $matches, $message);
        self::assertArrayHasKey('month', $matches, $message);
        self::assertArrayHasKey('value', $matches, $message);
        self::assertArrayHasKey('dayOfWeek', $matches, $message);

        $this->selectRecurrenceOption('[data-related-field="monthOfYear"]', $matches['month']);

        if ('Day' === $matches['value']) {
            $dayInput = $this->find('css', 'input[data-related-field="dayOfMonth"]');
            self::assertNotNull(
                $dayInput,
                "Recurrence days input not found (input[data-related-field='dayOfMonth'])"
            );
            $dayInput->setValue($matches['dayOfWeek']);
        } else {
            $this->selectRecurrenceOption('select[data-related-field="instance"]', $matches['value']);
            $this->selectRecurrenceOption('select[data-related-field="dayOfWeek"]', $matches['dayOfWeek']);
        }
    }

    /**
     * Find select, assert it and choose provided recurrence option
     *
     * @param string $selectLocator
     * @param string $option
     */
    private function selectRecurrenceOption($selectLocator, $option)
    {
        $typeSelect = $this->find('css', $selectLocator);
        self::assertNotNull($typeSelect, "Recurrence type select not found $selectLocator");

        $typeSelect->selectOption($option);
    }
}
