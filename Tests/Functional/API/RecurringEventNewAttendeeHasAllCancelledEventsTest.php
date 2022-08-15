<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class RecurringEventNewAttendeeHasAllCancelledEventsTest extends AbstractUseCaseTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testNewAttendeeOfRecurringEventHasSameExceptions()
    {
        $this->checkPreconditions();

        /** @var User $attendeeUser1 */
        $attendeeUser1 = $this->getReference('oro_calendar:user:system_user_1');
        $attendee1 = [
            'displayName' => sprintf('%s %s', $attendeeUser1->getFirstName(), $attendeeUser1->getLastName()),
            'email'       => $attendeeUser1->getEmail(),
            'status'      => Attendee::STATUS_NONE,
            'type'        => Attendee::TYPE_REQUIRED,
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
            'attendees'   => [$attendee1],
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
                        'userId' => $attendeeUser1->getId()
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
                        'userId' => $attendeeUser1->getId()
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
                        'userId' => $attendeeUser1->getId()
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
                        'userId' => $attendeeUser1->getId()
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
                        'userId' => $attendeeUser1->getId()
                    ]
                ]
            ]
        ];

        // Check events for owner user, all 5 events are returned
        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        // Check events for attendee user, all 5 events are returned
        $user1Calendar = $this->getUserCalendar($attendeeUser1);

        $actualEvents = $this->getAllCalendarEvents($user1Calendar->getId());
        $expectedUser1CalendarEventData = $this->changeExpectedDataCalendarId(
            $expectedCalendarEventData,
            $user1Calendar->getId()
        );
        $this->assertCalendarEvents($expectedUser1CalendarEventData, $actualEvents);

        $this->assertEventQuantityInDB(2);

        $exceptionEventStart = '2016-07-02T10:00:00+00:00';
        $exceptionEventEnd = '2016-07-02T10:30:00+00:00';

        $exceptionCalendarEventData = [
            'isCancelled'      => true,
            'originalStart'    => $calendarEventData['start'],
            'title'            => $calendarEventData['title'],
            'description'      => $calendarEventData['description'],
            'allDay'           => false,
            'attendees'        => [$attendee1],
            'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
            'start'            => $exceptionEventStart,
            'end'              => $exceptionEventEnd,
            'recurringEventId' => $recurringCalendarEventId,
        ];

        // Add exception event for first occurrence
        $exceptionCalendarEventExceptionId = $this->addCalendarEventViaAPI($exceptionCalendarEventData);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);

        // Check events for owner user, cancelled exception is not returned
        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        unset($expectedCalendarEventData[0]);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        // Check events for attendee user, cancelled exception is not returned
        unset($expectedUser1CalendarEventData[0]);
        $actualEvents = $this->getAllCalendarEvents($user1Calendar->getId());
        $this->assertCalendarEvents($expectedUser1CalendarEventData, $actualEvents);

        $this->assertEventQuantityInDB(4);
        $this->assertCalendarEventAttendeesCount($exceptionCalendarEventExceptionId, 1);

        // Add one more attendee to entire series of recurring event

        /** @var User $attendeeUser2 */
        $attendeeUser2 = $this->getReference('oro_calendar:user:system_user_2');
        $attendee2 = [
            'displayName' => sprintf('%s %s', $attendeeUser2->getFirstName(), $attendeeUser2->getLastName()),
            'email'       => $attendeeUser2->getEmail(),
            'status'      => Attendee::STATUS_NONE,
            'type'        => Attendee::TYPE_REQUIRED,
        ];

        $calendarEventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event',
            'attendees'   => [$attendee1, $attendee2],
        ];

        $this->updateCalendarEventViaAPI(
            $recurringCalendarEventId,
            $calendarEventData
        );

        $expectedCalendarEventData = array_map(
            function ($eventData) use ($attendeeUser2) {
                $eventData['attendees'][] = [
                    'userId' => $attendeeUser2->getId()
                ];
                return $eventData;
            },
            $expectedCalendarEventData
        );

        $expectedUser1CalendarEventData = $this->changeExpectedDataCalendarId(
            $expectedCalendarEventData,
            $user1Calendar->getId()
        );

        // Check events for owner user, cancelled exception is not returned
        $actualEvents = $this->getAllCalendarEvents(self::DEFAULT_USER_CALENDAR_ID);
        $this->assertCalendarEvents($expectedCalendarEventData, $actualEvents);

        // Check events for attendee user, cancelled exception is not returned
        $actualEvents = $this->getAllCalendarEvents($user1Calendar->getId());
        $this->assertCalendarEvents($expectedUser1CalendarEventData, $actualEvents);

        // Check events for new attendee user, cancelled exception is not returned
        $user2Calendar = $this->getUserCalendar($attendeeUser2);
        $actualEvents = $this->getAllCalendarEvents($user2Calendar->getId());
        $expectedUser2CalendarEventData = $this->changeExpectedDataCalendarId(
            $expectedCalendarEventData,
            $user2Calendar->getId()
        );
        $this->assertCalendarEvents($expectedUser2CalendarEventData, $actualEvents);
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
}
