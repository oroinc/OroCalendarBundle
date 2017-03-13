<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Event extends OroForm
{
    const REPEATS_DROP_DOWN = 'Repeats';
    const COLOR_FIELD_NAME = 'Color';
    const ALLOWED_COLORS_MAPPING = [
        'Cornflower Blue' => '#5484ED',
        'Melrose' => '#A4BDFC',
        'Turquoise' => '#46D6DB',
        'Riptide' => '#7AE7BF',
        'Apple green' => '#51B749',
        'Dandelion yellow' => '#FBD75B',
        'Orange' => '#FFB878',
        'Vivid Tangerine' => '#FF887C',
        'Alizarin Crimson' => '#DC2127',
        'Mauve' => '#DBADFF',
        'Mercury' => '#E1E1E1'
    ];

    /**
     * Set repeating type drop down
     * Like: Daily/Weekly/Monthly/Yearly
     *
     * @param $type
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
                self::assertArrayHasKey(
                    $value,
                    self::ALLOWED_COLORS_MAPPING,
                    "Color with name $value not found. Known names: " . print_r(self::ALLOWED_COLORS_MAPPING, true)
                );

                $button = self::ALLOWED_COLORS_MAPPING[$value];
                $this->find('css', "[data-color='$button']")->click();
                continue;
            }

            $value = self::normalizeValue($value);
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
