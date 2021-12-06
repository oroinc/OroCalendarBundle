<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

class CalendarEventWithEmptyAttendeesSavedTest extends AbstractUseCaseTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCalendarEventWasSavedEvenIfAttendeesRequestParameterIsEmpty()
    {
        $this->checkPreconditions();

        $calendarEventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-07-02T09:00:00+00:00',
            'end'         => '2016-07-02T09:30:00+00:00',
            'attendees'   => '',
        ];
        $recurringCalendarEventId = $this->addCalendarEventViaAPI($calendarEventData);
        $this->getEntityManager()->clear();

        $calendarEvent = $this->getCalendarEventById($recurringCalendarEventId);
        $this->assertNotNull($calendarEvent);

        $expectedCalendarEvents = [
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => $calendarEventData['start'],
                'end'         => $calendarEventData['end'],
                'attendees'   => []
            ],
        ];

        $actualCalendarEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEvents, $actualCalendarEvents);
    }

    private function checkPreconditions(): void
    {
        $result = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);

        $this->assertEmpty($result);
    }

    private function getAllCalendarEvents(int $calendarId): array
    {
        $request = [
            'calendar'    => $calendarId,
            'start'       => '2016-06-26T00:00:00+00:00',
            'end'         => '2016-08-07T00:00:00+00:00',
            'subordinate' => true,
        ];

        return $this->getAllCalendarEventsViaAPI($request);
    }
}
