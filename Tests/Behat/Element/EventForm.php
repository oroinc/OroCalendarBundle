<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class EventForm extends OroForm
{
    const REPEATS_DROP_DOWN = 'Repeats';
    const COLOR_FIELD_NAME = 'Color';

    /**
     * Set repeating type drop down
     * Like: Daily/Weekly/Monthly/Yearly
     *
     * @param string $type
     */
    public function setRecurrenceType($type)
    {
        $this->fillField(self::REPEATS_DROP_DOWN, $type);
    }

    /**
     * Set recurrence parameters from TableNode
     */
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as list($label, $value)) {
            if ($label == self::REPEATS_DROP_DOWN) {
                $this->fillField('Repeat', true);
                $this->setRecurrenceType($value);
            }

            if ($label == self::COLOR_FIELD_NAME) {
                $this->elementFactory->wrapElement('Simple Color Picker Field', $this)->setValue($value);
                continue;
            }

            $locator = isset($this->options['mapping'][$label]) ? $this->options['mapping'][$label] : $label;
            $value = self::normalizeValue($value);

            $field = $this->findField($locator);
            self::assertNotNull($field, "Element $label not found");
            $field->setValue($value);
        }
    }

    /**
     * @param string $name
     * @return \Behat\Mink\Element\NodeElement|Element|null
     */
    public function findField($name)
    {
        if ($this->elementFactory->hasElement($name)) {
            return $this->elementFactory->createElement($name);
        } else {
            return parent::findField($name);
        }
    }
}
