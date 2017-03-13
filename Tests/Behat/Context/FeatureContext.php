<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\CalendarBundle\Tests\Behat\Element\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Behat\Element\Event;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * Asserts event data on "My calendar" page
     *
     * Example: Then I should see "Daily weekday never ending Event" in calendar with:
     *               | Description   | testfull desc          |
     *               | Start         | today                  |
     *
     * @Then /^(?:|I )should see "(?P<name>.*)" in calendar with:$/
     */
    public function iShouldSeeInCalendarWith($name, TableNode $table)
    {
        /** @var Calendar $calendar */
        $calendar = $this->elementFactory->createElement('Calendar');
        $calendarEvent = $calendar->getCalendarEvent($name);
        $itemInfo = $calendarEvent->getCalendarItemInfo();

        foreach ($table->getRows() as list($name, $value)) {
            self::assertArrayHasKey($name, $itemInfo);

            if ($itemInfo[$name] instanceof \DateTime) {
                $value = new \DateTime($value);
                $value->setTime(0, 0, 0);
            }

            $value = Form::normalizeValue($value);

            self::assertEquals($value, $itemInfo[$name]);
        }

        if ($this->getPage()->hasButton('close')) {
            $this->getPage()->pressButton('close');
        }
    }
}
