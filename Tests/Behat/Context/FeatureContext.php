<?php

namespace Oro\Bundle\CalendarBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Oro\Bundle\CalendarBundle\Tests\Behat\Element\Calendar;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

/**
 * Calendar feature for automatic test
 */
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
            $itemInfoValue = $itemInfo->get($label);
            if ($itemInfoValue instanceof \DateTimeInterface) {
                $itemInfoValue = $itemInfoValue->format(\DateTimeInterface::ATOM);
            }
            self::assertEquals($value, $itemInfoValue);
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
     * @Given /^(?:|I )go to month page by name "(?P<monthName>.*)"$/
     */
    public function iGoToMonthPageByName(string $monthName)
    {
        $calendar = $this->getCalendar();
        $currentMonth = $calendar->getCurrentMonth();
        if (strstr($currentMonth, $monthName)) {
            return;
        }

        for ($iteration = 1; $iteration <= 12; $iteration++) {
            $calendar->goToNextPage();
            $this->waitForAjax();
            $currentMonth = $calendar->getCurrentMonth();
            if (strstr($currentMonth, $monthName)) {
                return;
            }
        }
    }

    /**
     * @Given /^(?:|I )go to today calendar page$/
     */
    public function goToTodayCalendarPage()
    {
        $this->getCalendar()->go2Today();
    }

    /**
     * Asserts switching All-Day Event on and off doesn't change event start and end time
     *
     * Example: Then I check switching All-Day Event on and off doesn't change event start and end time
     *
     * @Then /^(?:|I )check switching All-Day Event on and off doesn't change event start and end time$/
     */
    public function checkAllDayOnOffPreservesEventTime()
    {
        $startField = $this->elementFactory->createElement('Start Datetime');
        $endField = $this->elementFactory->createElement('End Datetime');
        $allDay = $this->elementFactory->createElement('All Day Event');

        // Save event start and end time
        /** @var string $oldStartDateTime */
        $oldStartDateTime = $startField->getValue();
        /** @var string $oldEndDateTime */
        $oldEndDateTime = $endField->getValue();

        // Switch All-Day Event on and off
        $allDay->setValue(true);
        $this->waitForAjax();
        $allDay->setValue(false);
        $this->waitForAjax();

        // Compare datetimes before and after
        /** @var string $newStartDateTime */
        $newStartDateTime = $startField->getValue();
        /** @var string $newEndDateTime */
        $newEndDateTime = $endField->getValue();

        $compareFormat = 'Y-m-d H:i';

        self::assertTrue(
            $this->isDateTimesEqual($compareFormat, $newStartDateTime, $oldStartDateTime),
            'Start datetime was changed after switching All-Day Event on and off'
        );
        self::assertTrue(
            $this->isDateTimesEqual($compareFormat, $newEndDateTime, $oldEndDateTime),
            'End datetime was changed after switching All-Day Event on and off'
        );
    }

    /**
     * Asserts start and end dates are the same for calendar event
     *
     * Example: Then I check start and end dates are the same for calendar event
     *
     * @Then /^(?:|I )check start and end dates are the same for calendar event$/
     */
    public function checkStartEndDatesEqual()
    {
        $compareFormat = 'Y-m-d';

        self::assertTrue(
            $this->isStartEndDateTimesEqualForCalendarEvent($compareFormat),
            'Start and end dates are not equal'
        );
    }

    /**
     * Asserts start and end dates and times are the same for calendar event
     *
     * Example: Then I check start and end datetimes are the same for calendar event
     *
     * @Then /^(?:|I )check start and end datetimes are the same for calendar event$/
     */
    public function checkStartEndDateTimesEqual()
    {
        $compareFormat = 'Y-m-d H:i';

        self::assertTrue(
            $this->isStartEndDateTimesEqualForCalendarEvent($compareFormat),
            'Start and end datetimes are not equal'
        );
    }

    private function isStartEndDateTimesEqualForCalendarEvent(string $format): bool
    {
        $startField = $this->elementFactory->createElement('Start Datetime');
        $endField = $this->elementFactory->createElement('End Datetime');

        // Compare dates for start and end
        /** @var string $startDateTime */
        $startDateTime = $startField->getValue();
        /** @var string $endDateTime */
        $endDateTime = $endField->getValue();

        return $this->isDateTimesEqual($format, $startDateTime, $endDateTime);
    }

    private function isDateTimesEqual(string $format, string $inputDateTime1, string $inputDateTime2): bool
    {
        $dateTime1 = new \DateTime();
        $dateTime1->setTimestamp(strtotime($inputDateTime1));
        $formattedDateTime1 = $dateTime1->format($format);

        $dateTime2 = new \DateTime();
        $dateTime2->setTimestamp(strtotime($inputDateTime2));
        $formattedDateTime2 = $dateTime2->format($format);

        return $formattedDateTime1 === $formattedDateTime2;
    }
}
