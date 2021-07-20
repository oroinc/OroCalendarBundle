<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element\EventRecurrence;

class End extends AbstractEventRecurrence
{
    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        parent::setValue($value);

        $this->setRecurrence($this->getRecurrenceParams());
    }

    /**
     * Checks radio button for selected parameter and provides input if needed
     *
     * @param array $params
     */
    private function setRecurrence($params)
    {
        foreach ($params as $key => $value) {
            $this->findRadioByName($key)->click();

            if ('After' == $key) {
                $this->find('css', 'input[data-related-field="occurrences"]')
                    ->setValue($value);
                $this->findRadioByName($key)->click();
            }
            if ('By' == $key) {
                $this->setEndByDate($key, $value);
            }
        }
    }

    /**
     * Set event ending by specific date and time
     */
    private function setEndByDate($fieldLabel, $value)
    {
        $value = new \DateTime($value);
        $dateContainer = $this->findElementContains('Label', $fieldLabel)->getParent();

        $this->elementFactory->wrapElement(
            'DateTimePicker',
            $dateContainer
        )->setValue($value);
    }
}
