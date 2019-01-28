<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CalendarEvent extends Element
{
    /**
     * Fetch calendar event details from popup to array
     *
     * @return CalendarEventInfo
     */
    public function getCalendarItemInfo()
    {
        $this->click();

        return $this->getPage()->getElement('Calendar Event Info');
    }
}
