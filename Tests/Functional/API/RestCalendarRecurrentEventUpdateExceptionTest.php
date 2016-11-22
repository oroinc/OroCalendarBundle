<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * @dbIsolation
 * @dbReindex
 */
class RestCalendarRecurrentEventUpdateExceptionTest extends AbstractUseCaseTestCase
{
    /**
     * @dataProvider updateExceptionsTestData
     *
     * @param $changedEventData
     * @param $expectedCalendarEvents
     */
    public function testUpdateExceptionsCases($changedEventData, $expectedCalendarEvents)
    {
        $calendarEventId = $this->createRecurringEventWithExceptions();
        $eventData = $this->getCalendarEventData();
        $eventData = array_replace_recursive($eventData, $changedEventData);

        $this->updateCalendarEventViaAPI($calendarEventId, $eventData);

        $actualEvents = $this->getOrderedCalendarEventsViaAPI($this->getApiRequestData());
        $this->assertCalendarEvents($expectedCalendarEvents, $actualEvents);
        $this->deleteEventViaAPI($calendarEventId);
    }

    /**
     * @return int
     */
    protected function createRecurringEventWithExceptions()
    {
        $calendarEventData = $this->getCalendarEventData();
        $calendarEventId = $this->addCalendarEventViaAPI($calendarEventData);

        $exceptionsData = [
            [//canceled event exception
                'isCancelled'      => true,
                'title'            => 'Canceled Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'start'            => '2016-05-09T01:00:00+00:00',
                'allDay'           => false,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'recurringEventId' => $calendarEventId,
                'originalStart'    => '2016-05-09T01:00:00+00:00',
                'end'              => '2016-05-09T02:00:00+00:00',
            ],
            [//changed calendar event as exception
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'start'            => '2016-05-22T03:00:00+00:00',
                'allDay'           => false,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'recurringEventId' => $calendarEventId,
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ]
        ];

        foreach ($exceptionsData as $exceptionData) {
            $this->addCalendarEventViaAPI($exceptionData);
        }

        //make API request get event and make sure exceptions are applied
        $actualEvents = $this->getOrderedCalendarEventsViaAPI($this->getApiRequestData());
        $expectedCalendarEvents = [
            [
                'start' => '2016-05-22T03:00:00+00:00',
                'title' => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'end' => '2016-05-22T05:00:00+00:00',
                'recurringEventId' => $calendarEventId,
            ],
            [
                'start' => '2016-05-08T01:00:00+00:00',
                'end' => '2016-05-08T02:00:00+00:00',
                'title' => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'recurringEventId' => null,
            ],
        ];
        $this->assertCalendarEvents($expectedCalendarEvents, $actualEvents);

        return $calendarEventId;
    }

    /**
     * @return array
     */
    protected function getApiRequestData()
    {
        return [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-01T01:00:00+00:00',
            'end'         => '2016-07-03T01:00:00+00:00',
            'subordinate' => true,
        ];
    }

    /**
     * @return array
     */
    protected function getCalendarEventData()
    {
        return [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-04-25T01:00:00+00:00',
            'end'         => '2016-04-25T02:00:00+00:00',
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ]
        ];
    }

    /**
     * @return array
     */
    public function updateExceptionsTestData()
    {
        return [
            'updateExceptions is true' => [
                'changedEventData' => [
                    'recurrence' => ['endTime' => null, 'occurrences' => 3],
                    'updateExceptions' => true,
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-05-09T01:00:00+00:00',
                        'end' => '2016-05-09T02:00:00+00:00',
                        'title' => 'Test Recurring Event',
                        'description' => 'Test Recurring Event Description',
                        'recurringEventId' => null,
                    ],
                    [
                        'start' => '2016-05-08T01:00:00+00:00',
                        'end' => '2016-05-08T02:00:00+00:00',
                        'title' => 'Test Recurring Event',
                        'description' => 'Test Recurring Event Description',
                        'recurringEventId' => null,
                    ],
                ]
            ],
            'updateExceptions is true and recurrence is empty' => [
                'changedEventData' => [
                    'recurrence' => null,
                    'start' => '2016-05-25T01:00:00+00:00',
                    'end' => '2016-05-25T02:00:00+00:00',
                    'updateExceptions' => true,
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-05-25T01:00:00+00:00',
                        'end' => '2016-05-25T02:00:00+00:00',
                        'title' => 'Test Recurring Event',
                        'description' => 'Test Recurring Event Description',
                        'recurringEventId' => null,
                    ],
                ]
            ],
            'updateExceptions is false' => [
                'changedEventData' => [
                    'recurrence' => ['occurrences' => 3],
                    'updateExceptions' => false,
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-05-22T03:00:00+00:00',
                        'title' => 'Test Recurring Event Changed',
                        'description' => 'Test Recurring Event Description',
                        'end' => '2016-05-22T05:00:00+00:00',
                    ],
                    [
                        'start' => '2016-05-08T01:00:00+00:00',
                        'end' => '2016-05-08T02:00:00+00:00',
                        'title' => 'Test Recurring Event',
                        'description' => 'Test Recurring Event Description',
                        'recurringEventId' => null,
                    ],
                ]
            ],
            'updateExceptions is false and recurrence is empty' => [
                'changedEventData' => [
                    'recurrence' => null,
                    'start' => '2016-05-14T01:00:00+00:00',
                    'end' => '2016-05-14T02:00:00+00:00',
                    'updateExceptions' => false,
                ],
                'expectedCalendarEvents' => [
                    [
                        'start' => '2016-05-22T03:00:00+00:00',
                        'title' => 'Test Recurring Event Changed',
                        'description' => 'Test Recurring Event Description',
                        'end' => '2016-05-22T05:00:00+00:00',
                    ],
                    [
                        'start' => '2016-05-14T01:00:00+00:00',
                        'end' => '2016-05-14T02:00:00+00:00',
                        'title' => 'Test Recurring Event',
                        'description' => 'Test Recurring Event Description',
                        'recurringEventId' => null,
                    ],
                    [
                        'start' => '2016-05-09T01:00:00+00:00',
                        'end' => '2016-05-09T02:00:00+00:00',
                        'title' => 'Canceled Test Recurring Event',
                        'description' => 'Test Recurring Event Description',
                    ],
                ]
            ],
        ];
    }
}
