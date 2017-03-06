<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Event extends Element
{
    const REPEATS_DROP_DOWN = 'Repeats';

    public function setRecurrenceType($type)
    {
        $this->fillField(self::REPEATS_DROP_DOWN, $type);
    }

    public function fillRecurrence(TableNode $table)
    {
        $this->fillField('Repeat', true);

        foreach ($table->getRows() as list($name, $value)) {
            if ($name == self::REPEATS_DROP_DOWN) {
                $this->setRecurrenceType($value);
            }
            $this->getEventElement($name)->setValue($value);
        }
    }

    /**
     * @param $name
     * @return \Behat\Mink\Element\NodeElement|Element
     */
    protected function getEventElement($name)
    {
        if ($this->elementFactory->hasElement($name)) {
            $element = $this->elementFactory->createElement($name);
        } else {
            $element = $this->findField($name);
        }

        self::assertNotNull($element, "Element $name not found");

        return $element;
    }
}
