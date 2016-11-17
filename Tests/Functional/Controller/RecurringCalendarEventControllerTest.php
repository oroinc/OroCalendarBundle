<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @dbReindex
 */
class RecurringCalendarEventControllerTest extends WebTestCase
{
    const RECURRING_EVENT_TITLE = 'Test Creating/Updating Recurring Event';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData']);
    }

    public function testCreateEventWithRecurring()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_calendar_event_create'));
        $form    = $crawler->selectButton('Save and Close')->form();

        $recurringEventData = [
            'oro_calendar_event_form[title]' => self::RECURRING_EVENT_TITLE,
            'oro_calendar_event_form[description]' => 'Test Recurring Event Description',
            'oro_calendar_event_form[start]' => gmdate(DATE_RFC3339),
            'oro_calendar_event_form[end]' => gmdate(DATE_RFC3339),
            'oro_calendar_event_form[allDay]' => true,
            'oro_calendar_event_form[backgroundColor]' => '#FF0000',
            'oro_calendar_event_form[repeat]' => true,
            'oro_calendar_event_form[recurrence]' => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval' => 2,
                'startTime' => gmdate(DATE_RFC3339),
                'occurrences' => 10,
                'timeZone' => 'UTC'
            ]
        ];

        $form->setValues($recurringEventData);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Calendar event saved', $crawler->html());

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['title' => self::RECURRING_EVENT_TITLE]);

        $this->assertNotNull($calendarEvent);
        $this->assertNotNull($calendarEvent->getRecurrence());

        $this->assertEquals($calendarEvent->getRecurrence()->getRecurrenceType(), Recurrence::TYPE_DAILY);

        return $calendarEvent;
    }

    /**
     * @depends testCreateEventWithRecurring
     *
     * @param CalendarEvent $calendarEvent
     */
    public function testUpdateEventWithRecurring($calendarEvent)
    {
        $response = $this->client->requestGrid(
            'calendar-event-grid',
            ['calendar-event-grid[_filter][title][value]' => self::RECURRING_EVENT_TITLE]
        );
        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_event_update', ['id' => $result['id']])
        );
        $form    = $crawler->selectButton('Save and Close')->form();

        $recurringEventData = [
            'oro_calendar_event_form[title]' => self::RECURRING_EVENT_TITLE,
            'oro_calendar_event_form[description]' => 'Test Recurring Event Description',
            'oro_calendar_event_form[start]' => gmdate(DATE_RFC3339),
            'oro_calendar_event_form[end]' => gmdate(DATE_RFC3339),
            'oro_calendar_event_form[allDay]' => true,
            'oro_calendar_event_form[backgroundColor]' => '#FF0000',
            'oro_calendar_event_form[repeat]' => true,
            'oro_calendar_event_form[recurrence]' => [
                'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                'instance' => Recurrence::INSTANCE_FIRST,
                'dayOfWeek' => Recurrence::DAY_MONDAY,
                'interval' => 2,
                'startTime' => gmdate(DATE_RFC3339),
                'timeZone' => 'UTC'
            ]
        ];

        $form->setValues($recurringEventData);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Calendar event saved', $crawler->html());

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['title' => self::RECURRING_EVENT_TITLE]);

        $this->assertNotNull($calendarEvent);
        $this->assertNotNull($calendarEvent->getRecurrence());

        $this->assertEquals($calendarEvent->getRecurrence()->getRecurrenceType(), Recurrence::TYPE_MONTH_N_TH);
    }

    /**
     * @dataProvider recurringEventCreationDataProvider
     *
     * @param $recurringEventParameters
     */
    public function testCreateRecurringEvent($recurringEventParameters)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_calendar_event_create'));
        $form    = $crawler->selectButton('Save and Close')->form();

        $form["oro_calendar_event_form[repeat]"] = true;
        foreach ($recurringEventParameters as $name => $parameterValue) {
            if (is_array($parameterValue)) {
                foreach ($parameterValue as $key => $value) {
                    $form["oro_calendar_event_form[$name][$key]"] = $value;
                }
            } else {
                $form["oro_calendar_event_form[$name]"] = $parameterValue;
            }

        }

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Calendar event saved', $crawler->html());

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['title' => $recurringEventParameters['title']]);

        $this->assertNotNull($calendarEvent);
        $this->assertNotNull($calendarEvent->getRecurrence());

        $this->assertEquals(
            $calendarEvent->getRecurrence()->getRecurrenceType(),
            $recurringEventParameters['recurrence']['recurrenceType']
        );
    }

    /**
     * @return array
     */
    public function recurringEventCreationDataProvider()
    {
        return [
            'Daily' => [
                [
                    'title' => 'Test Daily Recurring Event',
                    'description' => 'Test Daily Recurring Event Description',
                    'start' => gmdate(DATE_RFC3339),
                    'end' => gmdate(DATE_RFC3339),
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_DAILY,
                        'interval' => 3,
                        'startTime' => gmdate(DATE_RFC3339),
                        'endTime' => null,
                        'occurrences' => null,
                        'timeZone' => 'UTC'
                    ]
                ]
            ],
            'Monthly' => [
                [
                    'title' => 'Test Monthly Recurring Event',
                    'description' => 'Test Monthly Recurring Event Description',
                    'start' => gmdate(DATE_RFC3339),
                    'end' => gmdate(DATE_RFC3339),
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_MONTHLY,
                        'interval' => 1,
                        'dayOfMonth' => 25,
                        'startTime' => gmdate(DATE_RFC3339),
                        'endTime' => null,
                        'occurrences' => 5,
                        'timeZone' => 'UTC'
                    ]
                ]
            ],
            'MonthNth' => [
                [
                    'title' => 'Test MonthNth Recurring Event',
                    'description' => 'Test MonthNth Recurring Event Description',
                    'start' => gmdate(DATE_RFC3339),
                    'end' => gmdate(DATE_RFC3339),
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                        'instance' => Recurrence::INSTANCE_FIRST,
                        'dayOfWeek' => Recurrence::DAY_MONDAY,
                        'interval' => 2,
                        'startTime' => gmdate(DATE_RFC3339),
                        'endTime' => null,
                        'occurrences' => null,
                        'timeZone' => 'UTC'
                    ]
                ]
            ],
            'Weekly' => [
                [
                    'title' => 'Test Weekly Recurring Event',
                    'description' => 'Test Weekly Recurring Event Description',
                    'start' => gmdate(DATE_RFC3339),
                    'end' => gmdate(DATE_RFC3339),
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_WEEKLY,
                        'interval' => 2,
                        'dayOfWeek' => [Recurrence::DAY_MONDAY, Recurrence::DAY_SUNDAY],
                        'startTime' => gmdate(DATE_RFC3339),
                        'endTime' => gmdate(DATE_RFC3339),
                        'occurrences' => null,
                        'timeZone' => 'UTC'
                    ]
                ]
            ],
            'Yearly' => [
                [
                    'title' => 'Test Yearly Recurring Event',
                    'description' => 'Test Yearly Recurring Event Description',
                    'start' => gmdate(DATE_RFC3339),
                    'end' => gmdate(DATE_RFC3339),
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_YEARLY,
                        'interval' => 12,
                        'dayOfMonth' => 25,
                        'monthOfYear' => 4,
                        'startTime' => gmdate(DATE_RFC3339),
                        'endTime' => null,
                        'occurrences' => 10,
                        'timeZone' => 'UTC'
                    ]
                ]
            ],
            'YearNth' => [
                [
                    'title' => 'Test YearNth Recurring Event',
                    'description' => 'Test YearNth Recurring Event Description',
                    'start' => gmdate(DATE_RFC3339),
                    'end' => gmdate(DATE_RFC3339),
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_YEAR_N_TH,
                        'instance' => Recurrence::INSTANCE_SECOND,
                        'dayOfWeek' => [Recurrence::DAY_MONDAY],
                        'interval' => 12,
                        'monthOfYear' => 4,
                        'startTime' => gmdate(DATE_RFC3339),
                        'endTime' => null,
                        'occurrences' => 10,
                        'timeZone' => 'UTC'
                    ]
                ]
            ],
        ];
    }
}
