<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CalendarEvent extends Element
{
    /**
     * Fetch calendar event details from popup to array
     *
     * @return array
     */
    public function getCalendarItemInfo()
    {
        $this->click();

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
