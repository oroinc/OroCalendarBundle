<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * Calendar element
 */
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
        $calendarEvent = $this->findElementContains('Calendar Event', $title);
        self::assertNotNull($calendarEvent, "Event $title not found in calendar grid");

        return $calendarEvent;
    }

    public function goToNextPage()
    {
        $nextButton = $this->find('css', 'button.fc-next-button');
        self::assertNotNull($nextButton, "Calendar 'next' button not found on current page");

        $nextButton->press();
    }

    public function getCurrentMonth(): string
    {
        return $this->find(
            'css',
            '.calendar-container .calendar .fc-header-toolbar .fc-center h2'
        )->getText();
    }

    public function go2Today()
    {
        $todayButton = $this->findButton('today');
        self::assertNotNull($todayButton, "Calendar 'today' button not found on current page");

        $todayButton->press();
    }
}
