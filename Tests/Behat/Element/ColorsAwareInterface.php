<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

interface ColorsAwareInterface
{
    /**
     * Retrieve colors available to choose on form
     *
     * @return array
     */
    public function getAvailableColors();
}
