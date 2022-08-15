<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\UserBundle\Entity\User;

class RecurringEventWithAttendeesAndAttendeesCleanTest extends AbstractUseCaseTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRecurringEventWithAttendeesAndDeletion()
    {
        $this->checkPreconditions();

        /** @var User $simpleUser */
        $simpleUser = $this->getReference('oro_calendar:user:system_user_1');
        $attendees = [
            [
                'displayName' => sprintf('%s %s', $simpleUser->getFirstName(), $simpleUser->getLastName()),
                'email'       => $simpleUser->getEmail(),
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $calendarEventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event',
            'allDay'      => false,
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-07-02T09:00:00+00:00',
            'end'         => '2016-07-02T09:30:00+00:00',
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 1,
                'dayOfWeek'      => ['saturday'],
                'startTime'      => '2016-07-01T00:00:00+00:00',
                'occurrences'    => 5,
                'endTime'        => '2016-07-30T00:00:00+00:00',
            ],
            'attendees'   => $attendees,
        ];

        // Create recurring event with 1 attendee
        $recurringCalendarEventId = $this->addCalendarEventViaAPI($calendarEventData);

        $expectedCalendarEventData = [
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => $calendarEventData['start'],
                'end'         => $calendarEventData['end'],
                'attendees'   => [
                    [
                        'userId' => $simpleUser->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-09T09:00:00+00:00',
                'end'         => '2016-07-09T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $simpleUser->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-16T09:00:00+00:00',
                'end'         => '2016-07-16T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $simpleUser->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-23T09:00:00+00:00',
                'end'         => '2016-07-23T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $simpleUser->getId()
                    ]
                ]
            ],
            [
                'title'       => $calendarEventData['title'],
                'description' => $calendarEventData['description'],
                'allDay'      => $calendarEventData['allDay'],
                'calendar'    => $calendarEventData['calendar'],
                'start'       => '2016-07-30T09:00:00+00:00',
                'end'         => '2016-07-30T09:30:00+00:00',
                'attendees'   => [
                    [
                        'userId' => $simpleUser->getId()
                    ]
                ]
            ]
        ];

        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        $simpleUserCalendar = $this->getUserCalendar($simpleUser);

        $expectedSimpleUserCalendarEventData = $expectedCalendarEventData;

        $actualEvents = $this->getAllCalendarEvents($simpleUserCalendar->getId());
        $expectedSimpleUserCalendarEventData = $this->changeExpectedDataCalendarId(
            $expectedSimpleUserCalendarEventData,
            $simpleUserCalendar->getId()
        );
        $this->assertCalendarEvents($expectedSimpleUserCalendarEventData, $actualEvents);

        $this->assertEventQuantityInDB(2);

        $exceptionEventStart = '2016-07-02T10:00:00+00:00';
        $exceptionEventEnd = '2016-07-02T10:30:00+00:00';

        $exceptionCalendarEventData = [
            'originalStart'    => $calendarEventData['start'],
            'title'            => $calendarEventData['title'],
            'description'      => $calendarEventData['description'],
            'allDay'           => false,
            'attendees'        => $attendees,
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'start'            => $exceptionEventStart,
            'end'              => $exceptionEventEnd,
            'recurringEventId' => $recurringCalendarEventId,
        ];

        // Add exception event for first occurrence
        $exceptionCalendarEventExceptionId = $this->addCalendarEventViaAPI($exceptionCalendarEventData);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);

        // Check events for owner user
        $expectedCalendarEventData[0] = $exceptionCalendarEventData;
        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        // Check events for attendee user
        $actualEvents = $this->getAllCalendarEvents($simpleUserCalendar->getId());
        $expectedSimpleUserCalendarEventData[0] = $exceptionCalendarEventData;
        $expectedSimpleUserCalendarEventData[0]['recurringEventId'] = $actualEvents[0]['recurringEventId'];
        $expectedSimpleUserCalendarEventData = $this->changeExpectedDataCalendarId(
            $expectedSimpleUserCalendarEventData,
            $simpleUserCalendar->getId()
        );
        $this->assertCalendarEvents($expectedSimpleUserCalendarEventData, $actualEvents);

        $this->assertEventQuantityInDB(4);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);

        // Remove all attendees and submit request to update exception event
        $exceptionCalendarEventData['attendees'] = [];

        $this->updateCalendarEventViaAPI(
            $exceptionCalendarEventExceptionId,
            $exceptionCalendarEventData
        );

        // Check events for owner user
        $expectedCalendarEventData[0] = $exceptionCalendarEventData;
        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        // Check events for attendee user, there should be 1 cancelled event for this user because it was removed
        // from the event
        $actualEvents = $this->getAllCalendarEvents($simpleUserCalendar->getId());
        unset($expectedSimpleUserCalendarEventData[0]);
        $this->assertCalendarEvents($expectedSimpleUserCalendarEventData, $actualEvents);

        // Check exception event was cancelled for attendee
        $canceledCalendarEvents = $this->getCanceledCalendarEvents();
        $this->assertCount(1, $canceledCalendarEvents);
    }

    private function checkPreconditions(): void
    {
        $result = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);

        $this->assertEmpty($result);
    }

    private function assertEventQuantityInDB(int $number): void
    {
        $allEvents = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->findAll();

        $this->assertCount($number, $allEvents);
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

    /**
     * @return CalendarEvent[]
     */
    private function getCanceledCalendarEvents(): array
    {
        return $this->getEntityManager()
            ->getRepository(CalendarEvent::class)
            ->findBy(['cancelled' => true]);
    }
}
