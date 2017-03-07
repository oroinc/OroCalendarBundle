<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Calendar extends Element
{
    /**
     * Find event link on calendar grid
     *
     * @param string $title
     * @return CalendarEvent
     */
    public function getCalendarEvent($title)
    {
        $this->pressButton('today');

        $calendarEvent = $this->findElementContains('Calendar Event', $title);
        self::assertNotNull($calendarEvent, "Event $title not found in calendar grid");

        return $calendarEvent;
    }
}
