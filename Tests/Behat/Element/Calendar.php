<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Calendar extends Element implements ColorsAwareInterface
{
    use EventColors {
        getAvailableColors as private getColors;
    }

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

    public function getAvailableColors()
    {
        $this->find('css', '.connection-item')->mouseOver();
        $this->find('css', ".context-menu-button")->click();

        return $this->getColors();
    }

    public function goToNextPage()
    {
        $nextButton = $this->find('css', '.fc-next-button');
        self::assertNotNull($nextButton, "Calendar 'next' button not found on current page");

        $nextButton->click();
    }

    public function go2Today()
    {
        $todayButton = $this->findButton('today');
        self::assertNotNull($todayButton, "Calendar 'today' button not found on current page");

        $todayButton->press();
    }
}
