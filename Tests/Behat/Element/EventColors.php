<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;

trait EventColors
{
    /**
     * Retrieve colors present on current form
     *
     * @return array
     */
    public function getAvailableColors()
    {
        $colorSpans = $this->findAll('css', '.simplecolorpicker.inline > span');
        $colors = [];

        self::assertNotEmpty($colorSpans, "Color blocks not found on current form");

        /** @var NodeElement $element */
        foreach ($colorSpans as $element) {
            $color = $element->getAttribute('data-color');

            if (!empty($color)) {
                $colors[] = $element->getAttribute('data-color');
            }
        }

        // last element is custom value block, so we remove it
        array_pop($colors);

        return $colors;
    }
}
