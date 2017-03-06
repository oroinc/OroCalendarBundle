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
        $item = $calendar->findEventLink($name);

        $itemInfo = $calendar->getCalendarItemInfo($item);

        foreach ($table->getRows() as list($name, $value)) {
            self::assertArrayHasKey($name, $itemInfo);

            if ($itemInfo[$name] instanceof \DateTime) {
                $value = new \DateTime($value);
                $value->setTime(0, 0, 0);
            }

            $value = Form::normalizeValue($value, $name);

            self::assertEquals($value, $itemInfo[$name]);
        }

        if ($this->getPage()->hasButton('close')) {
            $this->getPage()->pressButton('close');
        }
    }

    /**
     * Sets event recurrence parameters
     *
     * Example: And set event repeating:
     *               | Repeats         | Daily                |
     *               | DailyRecurrence | Repeat every:Weekday |
     * Or:
     *        | Repeats         | Daily                |
     *        | DailyRecurrence | Repeat every:22 days |
     * Or:
     *       | Repeats         | Weekly                                  |
     *       | WeeklyRecurrence| Repeat on:monday                        |
     *
     *       | WeeklyRecurrence| Repeat every:13 weeks, Repeat on:monday |
     * Or:
     *       | Repeats           | Monthly                |
     *       | MonthlyRecurrence | Repeat on:Day 10       |
     *
     *       | MonthlyRecurrence | Repeat on:First Sunday |
     * Or:
     *       | Repeats          | Yearly                   |
     *       | YearlyRecurrence | Repeat on:April Day 9    |
     *
     *       | YearlyRecurrence | Repeat on:May Second Day |
     *
     *
     * @Then /^(?:|I )set event repeating:$/
     */
    public function setEventRepeating(TableNode $table)
    {
        /** @var Event $event */
        $event = $this->elementFactory->createElement('Event');
        $event->fillRecurrence($table);
    }
}
