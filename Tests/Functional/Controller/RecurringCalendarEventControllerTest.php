<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\API\AbstractUseCaseTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class RecurringCalendarEventControllerTest extends AbstractUseCaseTestCase
{
    private const RECURRING_EVENT_TITLE = 'Test Creating/Updating Recurring Event';

    protected function setUp(): void
    {
        $this->markTestSkipped('CRM-7978');

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserData::class]);
    }

    public function testCreateEventWithRecurring()
    {
        $formData = [
            'title' => self::RECURRING_EVENT_TITLE,
            'description' => 'Test Recurring Event Description',
            'start' => '2016-04-25T01:00:00+00:00',
            'end' => '2016-04-25T02:00:00+00:00',
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                'instance' => Recurrence::INSTANCE_FIRST,
                'dayOfWeek' => [Recurrence::DAY_MONDAY],
                'interval' => 2,
                'startTime' => gmdate(DATE_RFC3339),
                'timeZone' => 'UTC'
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_calendar_event_create'),
            [
                'oro_calendar_event_form' => $formData
            ],
            [],
            []
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertStringContainsString('Calendar event saved', $crawler->html(), 'Calendar event not saved');

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->findOneBy(['title' => self::RECURRING_EVENT_TITLE]);

        $this->assertNotNull($calendarEvent);
        $this->assertNotNull($calendarEvent->getRecurrence());

        $this->assertEquals(Recurrence::TYPE_MONTH_N_TH, $calendarEvent->getRecurrence()->getRecurrenceType());
    }

    /**
     * @depends testCreateEventWithRecurring
     */
    public function testUpdateEventWithRecurring(): CalendarEvent
    {
        $response = $this->client->requestGrid(
            'calendar-event-grid',
            ['calendar-event-grid[_filter][title][value]' => self::RECURRING_EVENT_TITLE]
        );
        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $formData = [
            'title' => self::RECURRING_EVENT_TITLE,
            'description' => 'Test Recurring Event Description',
            'start' => '2016-04-25T01:00:00+00:00',
            'end' => '2016-04-25T02:00:00+00:00',
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'organizerDisplayName' => 'John Doe',
            'organizerEmail' => 'admin@example.com',
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval' => 5,
                'startTime' => '2016-04-25T01:00:00+00:00',
                'endTime' => '2016-06-10T01:00:00+00:00',
                'occurrences' => null,
                'timeZone' => 'UTC'
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_calendar_event_update', ['id' => $result['id']]),
            [
                'oro_calendar_event_form' => $formData
            ],
            [],
            []
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertStringContainsString('Calendar event saved', $crawler->html(), 'Calendar event not saved');

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->findOneBy(['title' => self::RECURRING_EVENT_TITLE]);

        $this->assertNotNull($calendarEvent);
        $this->assertNotNull($calendarEvent->getRecurrence());

        $this->assertEquals(Recurrence::TYPE_DAILY, $calendarEvent->getRecurrence()->getRecurrenceType());

        return $calendarEvent;
    }

    /**
     * @depends testUpdateEventWithRecurring
     */
    public function testUpdateExceptionsOnRecurrenceFieldsUpdate(CalendarEvent $calendarEvent): CalendarEvent
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        //add exceptions with API requests
        $this->addExceptions($calendarEvent);

        //update recurrence of calendar event, so all exceptions should be removed
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $formData = [
            'title' => self::RECURRING_EVENT_TITLE,
            'description' => 'Test Recurring Event Description',
            'start' => '2016-05-30T01:00:00+00:00',
            'end' => '2016-05-30T02:00:00+00:00',
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'organizerDisplayName' => 'John Doe',
            'organizerEmail' => 'admin@example.com',
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval' => 5,
                'startTime' => '2016-05-30T01:00:00+00:00',
                'endTime' => '2016-06-10T01:00:00+00:00',
                'occurrences' => null,
                'timeZone' => 'UTC'
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_calendar_event_update', ['id' => $calendarEvent->getId()]),
            [
                'oro_calendar_event_form' => $formData
            ],
            [],
            []
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertStringContainsString('Calendar event saved', $crawler->html(), 'Calendar event not saved');

        $this->initClient([], $this->generateWsseAuthHeader());

        //make API request get event and make sure exceptions are removed
        $actualEvents = $this->getOrderedCalendarEventsViaAPI($this->getApiRequestData($calendarEvent));
        $expectedCalendarEvents = [
            [
                'start' => '2016-06-09T01:00:00+00:00',
                'end' => '2016-06-09T02:00:00+00:00',
                'title' => $calendarEvent->getTitle(),
                'description' => $calendarEvent->getDescription(),
            ],
            [
                'start' => '2016-06-04T01:00:00+00:00',
                'end' => '2016-06-04T02:00:00+00:00',
                'title' => $calendarEvent->getTitle(),
                'description' => $calendarEvent->getDescription(),
            ],
            [
                'start' => '2016-05-30T01:00:00+00:00',
                'end' => '2016-05-30T02:00:00+00:00',
                'title' => $calendarEvent->getTitle(),
                'description' => $calendarEvent->getDescription(),
            ],
        ];
        $this->assertCalendarEvents($expectedCalendarEvents, $actualEvents);

        return $calendarEvent;
    }

    /**
     * @depends testUpdateExceptionsOnRecurrenceFieldsUpdate
     */
    public function testUpdateExceptionsOnEmptyRecurrence(CalendarEvent $calendarEvent)
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        //add exceptions with API requests
        $this->addExceptions($calendarEvent);

        //update recurrence of calendar event, so all exceptions should be removed
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $formData = [
            'title' => self::RECURRING_EVENT_TITLE,
            'description' => 'Test Recurring Event Description',
            'start' => '2016-05-30T01:00:00+00:00',
            'end' => '2016-05-30T02:00:00+00:00',
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'organizerDisplayName' => 'John Doe',
            'organizerEmail' => 'admin@example.com',
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_calendar_event_update', ['id' => $calendarEvent->getId()]),
            [
                'oro_calendar_event_form' => $formData
            ],
            [],
            []
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertStringContainsString('Calendar event saved', $crawler->html(), 'Calendar event not saved');

        $this->initClient([], $this->generateWsseAuthHeader());

        //make API request get event and make sure exceptions are removed
        $actualEvents = $this->getOrderedCalendarEventsViaAPI($this->getApiRequestData($calendarEvent));
        $expectedCalendarEvents = [
            [
                'id' => $calendarEvent->getId(),
                'start' => '2016-05-30T01:00:00+00:00',
                'end' => '2016-05-30T02:00:00+00:00',
                'title' => $calendarEvent->getTitle(),
                'description' => $calendarEvent->getDescription(),
                'recurringEventId' => null, //make sure it is not exception
                'isCancelled' => false,
            ],
        ];
        $this->assertCalendarEvents($expectedCalendarEvents, $actualEvents);

        $this->deleteEventViaAPI($calendarEvent->getId());
    }

    /**
     * @dataProvider recurringEventCreationDataProvider
     */
    public function testCreateRecurringEvent(
        array $recurringEventParameters,
        array $apiRequestParams,
        array $expectedCalendarEvents
    ) {
        $formData = [];
        foreach ($recurringEventParameters as $name => $parameterValue) {
            if (is_array($parameterValue)) {
                foreach ($parameterValue as $key => $value) {
                    $formData[$name][$key] = $value;
                }
            } else {
                $formData[$name] = $parameterValue;
            }
        }

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_calendar_event_create'),
            [
                'oro_calendar_event_form' => $formData
            ],
            [],
            []
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertStringContainsString('Calendar event saved', $crawler->html(), 'Calendar event not saved');

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->findOneBy(['title' => $recurringEventParameters['title']]);

        $this->assertNotNull($calendarEvent);
        $this->assertNotNull($calendarEvent->getRecurrence());

        $this->assertEquals(
            $calendarEvent->getRecurrence()->getRecurrenceType(),
            $recurringEventParameters['recurrence']['recurrenceType']
        );

        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => $apiRequestParams['start'],
            'end'         => $apiRequestParams['end'],
            'subordinate' => true,
        ];

        $this->initClient([], $this->generateWsseAuthHeader());
        $actualEvents = $this->getOrderedCalendarEventsViaAPI($request);
        $this->assertCalendarEvents($expectedCalendarEvents, $actualEvents);
        $this->deleteEventViaAPI($calendarEvent->getId());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function recurringEventCreationDataProvider(): array
    {
        return [
            'Daily' => [
                'recurringEventParameters' => [
                    'title' => 'Test Daily Recurring Event',
                    'description' => 'Test Daily Recurring Event Description',
                    'start' => '2016-04-25T01:00:00+00:00',
                    'end' => '2016-04-25T02:00:00+00:00',
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_DAILY,
                        'interval' => 5,
                        'startTime' => '2016-04-25T01:00:00+00:00',
                        'endTime' => '2016-06-10T01:00:00+00:00',
                        'occurrences' => null,
                        'timeZone' => 'UTC'
                    ]
                ],
                'apiRequestParams' => [
                    'start' => '2016-03-28T01:00:00+00:00',
                    'end' => '2016-05-01T01:00:00+00:00'
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-04-30T01:00:00+00:00',
                        'end' => '2016-04-30T02:00:00+00:00',
                    ],
                    [
                        'start' => '2016-04-25T01:00:00+00:00',
                        'end' => '2016-04-25T02:00:00+00:00',
                    ]
                ]
            ],
            'Monthly' => [
                'recurringEventParameters' => [
                    'title' => 'Test Monthly Recurring Event',
                    'description' => 'Test Monthly Recurring Event Description',
                    'start' => '2016-04-25T01:00:00+00:00',
                    'end' => '2016-04-25T02:00:00+00:00',
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_MONTHLY,
                        'interval' => 2,
                        'dayOfMonth' => 25,
                        'startTime' => '2016-04-25T01:00:00+00:00',
                        'endTime' => '2016-12-31T01:00:00+00:00',
                        'occurrences' => 3,
                        'timeZone' => 'UTC'
                    ]
                ],
                'apiRequestParams' => [
                    'start' => '2016-07-25T01:00:00+00:00',
                    'end' => '2016-09-04T01:00:00+00:00'
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-08-25T01:00:00+00:00',
                        'end' => '2016-08-25T02:00:00+00:00',
                    ]
                ]
            ],
            'MonthNth' => [
                'recurringEventParameters' => [
                    'title' => 'Test MonthNth Recurring Event',
                    'description' => 'Test MonthNth Recurring Event Description',
                    'start' => '2016-04-25T01:00:00+00:00',
                    'end' => '2016-04-25T02:00:00+00:00',
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                        'instance' => Recurrence::INSTANCE_FIRST,
                        'dayOfWeek' => [Recurrence::DAY_MONDAY],
                        'interval' => 2,
                        'startTime' => '2016-04-25T01:00:00+00:00',
                        'endTime' => '2016-08-01T01:00:00+00:00',
                        'occurrences' => null,
                        'timeZone' => 'UTC'
                    ]
                ],
                'apiRequestParams' => [
                    'start' => '2016-04-01T01:00:00+00:00',
                    'end' => '2016-09-01T01:00:00+00:00'
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-08-01T01:00:00+00:00',
                        'end' => '2016-08-01T02:00:00+00:00',
                    ],
                    [
                        'start' => '2016-06-06T01:00:00+00:00',
                        'end' => '2016-06-06T02:00:00+00:00',
                    ],
                ]
            ],
            'Weekly' => [
                'recurringEventParameters' => [
                    'title' => 'Test Weekly Recurring Event',
                    'description' => 'Test Weekly Recurring Event Description',
                    'start' => '2016-04-25T01:00:00+00:00',
                    'end' => '2016-04-25T02:00:00+00:00',
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_WEEKLY,
                        'interval' => 2,
                        'dayOfWeek' => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                        'startTime' => '2016-04-25T01:00:00+00:00',
                        'endTime' => null,
                        'occurrences' => 4,
                        'timeZone' => 'UTC'
                    ]
                ],
                'apiRequestParams' => [
                    'start' => '2016-05-01T01:00:00+00:00',
                    'end' => '2016-07-03T01:00:00+00:00'
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-05-22T01:00:00+00:00',
                        'end' => '2016-05-22T02:00:00+00:00',
                    ],
                    [
                        'start' => '2016-05-09T01:00:00+00:00',
                        'end' => '2016-05-09T02:00:00+00:00',
                    ],
                    [
                        'start' => '2016-05-08T01:00:00+00:00',
                        'end' => '2016-05-08T02:00:00+00:00',
                    ],
                ]
            ],
            'Yearly' => [
                'recurringEventParameters' => [
                    'title' => 'Test Yearly Recurring Event',
                    'description' => 'Test Yearly Recurring Event Description',
                    'start' => '2016-04-25T01:00:00+00:00',
                    'end' => '2016-04-25T02:00:00+00:00',
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_YEARLY,
                        'interval' => 12,
                        'dayOfMonth' => 25,
                        'monthOfYear' => 4,
                        'startTime' => '2016-04-25T01:00:00+00:00',
                        'endTime' => '2018-06-30T01:00:00+00:00',
                        'occurrences' => null,
                        'timeZone' => 'UTC'
                    ]
                ],
                'apiRequestParams' => [
                    'start' => '2018-01-01T01:00:00+00:00',
                    'end' => '2019-05-01T01:00:00+00:00'
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2018-04-25T01:00:00+00:00',
                        'end' => '2018-04-25T02:00:00+00:00',
                    ],
                ]
            ],
            'YearNth' => [
                'recurringEventParameters' => [
                    'title' => 'Test YearNth Recurring Event',
                    'description' => 'Test YearNth Recurring Event Description',
                    'start' => '2015-03-01T01:00:00+00:00',
                    'end' => '2015-03-01T02:00:00+00:00',
                    'allDay' => true,
                    'backgroundColor' => '#FF0000',
                    'recurrence' => [
                        'recurrenceType' => Recurrence::TYPE_YEAR_N_TH,
                        'instance' => Recurrence::INSTANCE_FIRST,
                        'dayOfWeek' => [Recurrence::DAY_MONDAY],
                        'interval' => 12,
                        'monthOfYear' => 4,
                        'startTime' => '2016-04-25T01:00:00+00:00',
                        'endTime' => '2020-03-01T01:00:00+00:00',
                        'occurrences' => null,
                        'timeZone' => 'UTC'
                    ]
                ],
                'apiRequestParams' => [
                    'start' => '2015-03-01T01:00:00+00:00',
                    'end' => '2018-03-01T01:00:00+00:00'
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2017-04-03T01:00:00+00:00',
                        'end' => '2017-04-03T02:00:00+00:00',
                    ],
                ]
            ],
        ];
    }

    private function getExceptionsData(CalendarEvent $calendarEvent): array
    {
        return [
            [//canceled event exception
                'isCancelled'      => true,
                'title'            => $calendarEvent->getTitle(),
                'description'      => $calendarEvent->getDescription(),
                'start'            => '2016-06-04T01:00:00+00:00',
                'allDay'           => $calendarEvent->getAllDay(),
                'calendar'         => $calendarEvent->getCalendar()->getId(),
                'recurringEventId' => $calendarEvent->getId(),
                'originalStart'    => '2016-06-04T01:00:00+00:00',
                'end'              => '2016-06-04T02:00:00+00:00',
            ],
            [//changed calendar event as exception
                'isCancelled'      => false,
                'title'            => $calendarEvent->getTitle() . ' Changed',
                'description'      => $calendarEvent->getDescription(),
                'start'            => '2016-05-30T03:00:00+00:00',
                'allDay'           => $calendarEvent->getAllDay(),
                'calendar'         => $calendarEvent->getCalendar()->getId(),
                'recurringEventId' => $calendarEvent->getId(),
                'originalStart'    => '2016-05-30T01:00:00+00:00',
                'end'              => '2016-05-30T05:00:00+00:00',
            ]
        ];
    }

    private function addExceptions(CalendarEvent $calendarEvent): void
    {
        foreach ($this->getExceptionsData($calendarEvent) as $exceptionData) {
            $this->addCalendarEventViaAPI($exceptionData);
        }

        //make API request get event and make sure exceptions are applied
        $request = $this->getApiRequestData($calendarEvent);
        $actualEvents = $this->getOrderedCalendarEventsViaAPI($request);
        $expectedCalendarEvents = [
            [
                'start' => '2016-06-09T01:00:00+00:00',
                'end' => '2016-06-09T02:00:00+00:00',
                'title' => $calendarEvent->getTitle(),
                'description' => $calendarEvent->getDescription(),
            ],
            [
                'start' => '2016-05-30T03:00:00+00:00',
                'title' => $calendarEvent->getTitle() . ' Changed',
                'description' => $calendarEvent->getDescription(),
                'end' => '2016-05-30T05:00:00+00:00',
            ]
        ];
        $this->assertCalendarEvents($expectedCalendarEvents, $actualEvents);
    }

    private function getApiRequestData(CalendarEvent $calendarEvent): array
    {
        return [
            'calendar'    => $calendarEvent->getCalendar()->getId(),
            'start'       => '2016-05-30T01:00:00+00:00',
            'end'         => '2016-07-04T01:00:00+00:00',
            'subordinate' => true,
        ];
    }
}
