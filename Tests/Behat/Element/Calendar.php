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
     * @return NodeElement
     */
    public function findEventLink($title)
    {
        $this->pressButton('today');
        $eventLocator = ".fc-title:contains($title)";
        $itemSpan = $this->find('css', $eventLocator);

        self::assertNotNull($itemSpan, "Event $title not found in calendar grid");
        $itemLink = $itemSpan->getParent()->getParent();

        return $itemLink;
    }

    /**
     * Fetch calendar event details from popup to array
     *
     * @param NodeElement $link
     * @return array
     */
    public function getCalendarItemInfo(NodeElement $link)
    {
        $link->click();
        $dataGroup = $this->getPage()->findAll('css', ".widget-content .responsive-block > .control-group");

        $calendarItemInfo = [];
        /** @var NodeElement $group */
        foreach ($dataGroup as $group) {
            $name = $group->find('css', 'label.control-label')->getText();
            $value = $group->find('css', '.controls > div')->getText();

            if (strtotime(trim($value))) {
                $value = new \DateTime($value);
                $value->setTime(0, 0, 0);
            }
            $calendarItemInfo[$name] = $value;
        }

        return $calendarItemInfo;
    }
}
