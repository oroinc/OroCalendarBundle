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
     *
     * @param TableNode $table
     */
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as list($name, $value)) {
            if ($name == self::REPEATS_DROP_DOWN) {
                $this->fillField('Repeat', true);
                $this->setRecurrenceType($value);
            }

            if ($name == self::COLOR_FIELD_NAME) {
                $this->elementFactory->wrapElement('Simple Color Picker Field', $this)->setValue($value);
                continue;
            }

            $value = self::normalizeValue($value);

            $field = $this->findField($name);
            self::assertNotNull($field, "Element $name not found");
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
