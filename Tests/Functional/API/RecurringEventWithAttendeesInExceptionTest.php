<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurringEventWithAttendeesInExceptionTest extends AbstractUseCaseTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDeleteExceptionWithAttendees()
    {
        $this->checkPreconditions();

        $startDate = '2016-02-07T09:00:00+00:00';
        $endDate = '2016-02-07T09:30:00+00:00';
        $exceptionStart = '2016-02-07T18:00:00+00:00';
        $exceptionEnd = '2016-02-07T18:30:00+00:00';

        $calendarEventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => $startDate,
            'end'         => $endDate,
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 1,
                'dayOfWeek'      => ['saturday'],
                'startTime'      => $startDate,
                'occurrences'    => 5,
                'endTime'        => null,
            ],
            'attendees'   => null,
        ];
        $calendarEventId = $this->addCalendarEventViaAPI($calendarEventData);
        $this->getCalendarEventById($calendarEventId);

        $exceptionData = [
            'isCancelled'      => false,
            'title'            => $calendarEventData['title'],
            'description'      => $calendarEventData['description'],
            'start'            => $exceptionStart,
            'allDay'           => $calendarEventData['allDay'],
            'calendar'         => $calendarEventData['calendar'],
            'recurringEventId' => $calendarEventId,
            'originalStart'    => '2016-02-13T09:00:00+00:00',
            'end'              => $exceptionEnd,
            'attendees'        => [
                [
                    'displayName' => 'system_user_1@example.com',
                    'email'       => 'system_user_1@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
            ]
        ];
        $mainExceptionCalendarEventId = $this->addCalendarEventViaAPI($exceptionData);
        $mainExceptionEvent = $this->getCalendarEventById($mainExceptionCalendarEventId);

        $simpleUser = $this->getReference('oro_calendar:user:system_user_1');
        $expectedEventsData = [
            [
                'start'       => '2016-03-12T09:00:00+00:00',
                'end'         => '2016-03-12T09:30:00+00:00',
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
                'isCancelled' => false,
                'attendees'   => [],
            ],
            [
                'start'       => '2016-03-05T09:00:00+00:00',
                'end'         => '2016-03-05T09:30:00+00:00',
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
                'isCancelled' => false,
                'attendees'   => [],
            ],
            [
                'start'       => '2016-02-27T09:00:00+00:00',
                'end'         => '2016-02-27T09:30:00+00:00',
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
                'isCancelled' => false,
                'attendees'   => [],
            ],
            [
                'start'       => '2016-02-20T09:00:00+00:00',
                'end'         => '2016-02-20T09:30:00+00:00',
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
                'isCancelled' => false,
                'attendees'   => [],
            ],
            [
                'start'       => $exceptionStart,
                'end'         => $exceptionEnd,
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
                'isCancelled' => false,
                'attendees'   => [
                    [
                        'userId' => $simpleUser->getId()
                    ]
                ],
            ],
        ];
        $actualEvents = $this->getCalendarEventsByCalendarViaAPI(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedEventsData, $actualEvents);

        $simpleUserCalendar = $this->getUserCalendar($simpleUser);
        $expectedSimpleUserEventsData = [
            [
                'start'       => $exceptionStart,
                'end'         => $exceptionEnd,
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $simpleUserCalendar->getId(),
                'isCancelled' => false,
                'attendees'   => [
                    [
                        'userId' => $simpleUser->getId()
                    ]
                ],
            ],
        ];
        $actualEvents = $this->getCalendarEventsByCalendarViaAPI($simpleUserCalendar->getId());
        $this->assertCalendarEvents($expectedSimpleUserEventsData, $actualEvents);

        $recurringCalendarEvents = $this->getRecurringCalendarEventsFromDB();
        $this->assertCount(2, $recurringCalendarEvents);

        $calendarEventExceptions = $this->getCalendarEventExceptionsFromDB();
        $this->assertCount(1, $calendarEventExceptions);

        $cancelRequest = [
            'isCancelled'      => true,
            'title'            => $exceptionData['title'],
            'description'      => $exceptionData['description'],
            'start'            => $exceptionData['start'],
            'allDay'           => $exceptionData['allDay'],
            'calendar'         => $exceptionData['calendar'],
            'recurringEventId' => $exceptionData['recurringEventId'],
            'originalStart'    => $exceptionData['originalStart'],
            'end'              => $exceptionData['end'],
        ];
        $this->updateCalendarEventViaAPI($mainExceptionEvent->getId(), $cancelRequest);
        unset($expectedEventsData[4]);

        $actualEvents = $this->getCalendarEventsByCalendarViaAPI(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedEventsData, $actualEvents);

        $calendarEventExceptions = $this->getCalendarEventExceptionsFromDB();
        $this->assertCount(1, $calendarEventExceptions);
        /** @var CalendarEvent $calendarEventException */
        $calendarEventException = reset($calendarEventExceptions);
        $this->assertTrue($calendarEventException->isCancelled());

        $this->deleteEventViaAPI($mainExceptionEvent->getId());

        $expectedEventsData[5]['start'] = '2016-02-13T09:00:00+00:00';
        $expectedEventsData[5]['end'] = '2016-02-13T09:30:00+00:00';
        $actualEvents = $this->getCalendarEventsByCalendarViaAPI(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedEventsData, $actualEvents);

        $actualEvents = $this->getCalendarEventsByCalendarViaAPI($simpleUserCalendar->getId());
        $this->assertCalendarEvents([], $actualEvents);

        $recurringCalendarEvents = $this->getRecurringCalendarEventsFromDB();
        $this->assertCount(1, $recurringCalendarEvents);

        $calendarEventExceptions = $this->getCalendarEventExceptionsFromDB();
        $this->assertCount(0, $calendarEventExceptions);
    }

    private function checkPreconditions(): void
    {
        $result = $this->getCalendarEventsByCalendarViaAPI(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertEmpty($result);
    }

    private function getCalendarEventsByCalendarViaAPI(int $calendarId): array
    {
        $request = [
            'calendar'    => $calendarId,
            'start'       => '2016-02-06T00:00:00+00:00',
            'end'         => '2016-04-15T00:00:00+00:00',
            'subordinate' => true,
        ];

        return $this->getOrderedCalendarEventsViaAPI($request);
    }
}
