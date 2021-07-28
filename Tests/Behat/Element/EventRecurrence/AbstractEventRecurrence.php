<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element\EventRecurrence;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

abstract class AbstractEventRecurrence extends Element
{
    const REPEAT_EVERY_KEY = 'Repeat every';
    const REPEAT_ON_KEY = 'Repeat on';

    private $recurrenceParams = [];

    private $intervalRecurrenceSet = false;

    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $this->recurrenceParams = $this->fetchParamsToArray($values);
        $this->setIntervalRecurrence();
    }

    /**
     * Recurrence controls united in groups
     * This methods retrieve control container by its label
     *
     * @param $value
     * @param bool $subgroup
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function findValueGroup($value, $subgroup = false)
    {
        $nameElement = $this->find('css', "span:contains('$value'), label:contains('$value')");
        self::assertNotNull($nameElement, "Value group for name '$value' not found in recurrence block");

        if ($subgroup) {
            return $nameElement->getParent();
        }

        return $nameElement->getParent()->getParent();
    }

    /**
     * @return bool
     */
    public function isIntervalRecurrenceSet()
    {
        return $this->intervalRecurrenceSet;
    }

    /**
     * Retrieve radio input by group label
     *
     * @param string $name
     * @return Element
     */
    protected function findRadioByName($name)
    {
        $valueGroup = $this->findValueGroup($name, true);

        /** @var Element $option */
        $option = $valueGroup->find('css', 'input[type="radio"]');
        self::assertNotNull($option, "Radio input not found for $name");

        return $option;
    }

    /**
     * Fetch strings params from TableNode row to array
     * Example: "Repeat Every:Weekday, Ends:Never"
     *              parsed to ['Repeat Every' => 'Weekday', 'Ends' => 'Never']
     *
     * @param string $params
     * @return array
     */
    private function fetchParamsToArray($params)
    {
        $items = explode(',', $params);

        $parsed = [];
        $delimiter = ':';
        foreach ($items as $item) {
            $item = trim($item);

            if (str_contains($item, $delimiter)) {
                [$key, $value] = explode($delimiter, $item, 2);
            } else {
                $value = null;
                $key = $item;
            }

            $parsed[$key] = $value;
        }

        return $parsed;
    }

    /**
     * Fills interval input for event numeric triggering
     * Returns bool for methods events where only one interval type required
     *
     * @param $count
     * @param $valueGroupName
     * @return bool
     */
    protected function setNumericRecurrence($count, $valueGroupName)
    {
        $matches = [];
        preg_match('/(?P<count>\d+) \w+/', $count, $matches);

        if (3 !== count($matches)) {
            return false;
        }

        self::assertArrayHasKey(
            'count',
            $matches,
            "Bad recurrence format. Example: 'Repeat every:1 year' or 'Repeat every:25 days'"
        );

        $valueGroup = $this->findValueGroup($valueGroupName);
        $input = $valueGroup->find('css', '[data-related-field="interval"]');
        $input->setValue($matches['count']);

        $radio = $input->getParent()->find('css', 'input[type="radio"]');
        if (!empty($radio)) {
            $radio->click();
        }

        return true;
    }

    protected function getIntervalRecurrenceValue()
    {
        if (!key_exists(self::REPEAT_EVERY_KEY, $this->recurrenceParams)) {
            return null;
        }

        return $this->recurrenceParams[self::REPEAT_EVERY_KEY];
    }

    /**
     * Checks radio button for interval parameter and provides
     * input if "Repeat every" parameter is set
     *
     * @return bool
     */
    protected function setIntervalRecurrence()
    {
        $repeatEvery = $this->getIntervalRecurrenceValue();

        if (!empty($repeatEvery)) {
            $this->intervalRecurrenceSet = $this->setNumericRecurrence($repeatEvery, self::REPEAT_EVERY_KEY);
        }

        return $this->intervalRecurrenceSet;
    }

    /**
     * @param string|null $key
     * @return array
     */
    public function getRecurrenceParams($key = null)
    {
        if (empty($key)) {
            $response = $this->recurrenceParams;
        } else {
            $response = $this->recurrenceParams[$key];
        }

        return $response;
    }
}
