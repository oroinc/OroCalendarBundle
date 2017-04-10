<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\CalendarBundle\Tests\Behat\Element\Calendar;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /**
     * @return Calendar
     */
    private function getCalendar()
    {
        return $this->elementFactory->createElement('Calendar');
    }

    /**
     * Asserts event data on "My calendar" page
     *
     * Example: Then I should see "Daily weekday never ending Event" in calendar with:
     *               | Description   | testfull desc          |
     *               | Start         | today                  |
     *
     * @Then /^(?:|I )should see "(?P<eventTitle>.*)" in calendar with:$/
     */
    public function iShouldSeeInCalendarWith($eventTitle, TableNode $table)
    {
        $calendarEvent = $this->getCalendar()->getCalendarEvent($eventTitle);
        $this->waitForAjax();
        $itemInfo = $calendarEvent->getCalendarItemInfo();

        foreach ($table->getRows() as list($label, $value)) {
            $value = Form::normalizeValue($value);
            self::assertEquals($value, $itemInfo->get($label));
        }

        $itemInfo->close();
    }

    /**
     * @Given /^(?:|I )go to next calendar page$/
     */
    public function iGoToNextCalendarPage()
    {
        $this->getCalendar()->goToNextPage();
    }

    /**
     * @Given /^(?:|I )go to today calendar page$/
     */
    public function goToTodayCalendarPage()
    {
        $this->getCalendar()->go2Today();
    }
}
