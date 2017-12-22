<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * This element used on My Calendar page to get access for the tasks event color picker
 */
class MyCalendarChooseColorMenu extends Element
{
    public function click()
    {
        if (!$this->isIsset()) {
            self::fail('My Tasks Choose Color Menu is not found');
        }

        if (!$this->isVisible()) {
            $this->getParent()->getParent()->mouseOver();
            self::assertTrue($this->isVisible(), 'My Tasks Choose Color Menu is not visible');
        }

        $this->spin(function (MyCalendarChooseColorMenu $menu) {
            return $menu->getParent()->find('css', 'ul.dropdown-menu')->isVisible();
        }, 3);

        parent::click();
    }
}
