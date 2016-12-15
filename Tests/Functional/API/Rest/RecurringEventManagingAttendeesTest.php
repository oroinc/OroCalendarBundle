<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers managing attendees of recurring events.
 *
 * Operations covered:
 * - adding guests to exception and then adding the same guests to the all series
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 * - update event (oro_api_put_calendarevent)
 * - get events (oro_api_get_calendarevents)
 *
 * @dbIsolationPerTest
 */
class RecurringEventManagingAttendeesTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);  // force load fixtures
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateAddedUserAsAttendeeOfExceptionsAfterAllSeriesChanges()
    {
        // Step 1. Create new recurring event without attendees
        // Recurring event with occurrences: 2016-11-14, 2016-11-15, 2016-11-16
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-11-14T01:00:00+00:00',
            'end'         => '2016-11-14T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-11-14T01:00:00+00:00',
                'occurrences'    => 3
            ]
        ];
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
        $recurringEventId = $response['id'];

        // Step 2. Create exception for the recurring event, exception represents changed event with new attendees
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-11-15T03:00:00+00:00',
            'end'              => '2016-11-15T05:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEventId,
            'originalStart'    => '2016-11-15T01:00:00+00:00',
            'attendees' => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email' => 'foo_user_1@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                    'email' => 'foo_user_2@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ]
            ],
        ];
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
        $changedEventExceptionId = $response['id'];

        // Step 3. Change recurring event with the same attendees as in exception
        $eventData['attendees'] = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email' => 'foo_user_1@example.com',
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEventId]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_ACCEPTED,
                'editableInvitationStatus' => true,
            ],
            $response
        );

        // Step 4. Get events of added user via API and verify result is expected
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                        'start'       => '2016-11-13T01:00:00+00:00',
                        'end'         => '2016-11-19T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);

        $recurringEvent = $this->getEntity(CalendarEvent::class, $recurringEventId)
            ->getChildEvents()
            ->getValues();
        $recurringEvent = $recurringEvent[0];
        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email' => 'foo_user_1@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
                'createdAt' => $recurringEvent->getAttendeeByEmail('foo_user_1@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $recurringEvent->getAttendeeByEmail('foo_user_1@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
                'createdAt' => $recurringEvent->getAttendeeByEmail('foo_user_2@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $recurringEvent->getAttendeeByEmail('foo_user_2@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ],
        ];

        $changedEventException = $this->getEntity(CalendarEvent::class, $changedEventExceptionId)
            ->getChildEvents()
            ->getValues();
        $changedEventException = $changedEventException[0];

        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-11-14T01:00:00+00:00',
                'end'              => '2016-11-14T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $changedEventException->getId(),
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-11-15T03:00:00+00:00',
                'end'              => '2016-11-15T05:00:00+00:00',
                'attendees'        => [
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                        'email' => 'foo_user_1@example.com',
                        'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                        'status' => Attendee::STATUS_ACCEPTED,
                        'type' => Attendee::TYPE_REQUIRED,
                        'createdAt' => $changedEventException->getAttendeeByEmail('foo_user_1@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt' => $changedEventException->getAttendeeByEmail('foo_user_1@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                        'email' => 'foo_user_2@example.com',
                        'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                        'status' => Attendee::STATUS_ACCEPTED,
                        'type' => Attendee::TYPE_REQUIRED,
                        'createdAt' => $changedEventException->getAttendeeByEmail('foo_user_2@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt' => $changedEventException->getAttendeeByEmail('foo_user_2@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ],
                ]
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-11-16T01:00:00+00:00',
                'end'              => '2016-11-16T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRemovingAttendeesOfExceptionsAfterAllSeriesChanges()
    {
        // Step 1. Create new recurring event with attendees
        // Recurring event with occurrences: 2016-11-14, 2016-11-15, 2016-11-16
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-11-14T01:00:00+00:00',
            'end'         => '2016-11-14T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email' => 'foo_user_1@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                    'email' => 'foo_user_2@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ],
            ],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-11-14T01:00:00+00:00',
                'occurrences'    => 3
            ]
        ];
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
        $recurringEventId = $response['id'];

        // Step 2. Create exception for the recurring event without attendees
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-11-15T03:00:00+00:00',
            'end'              => '2016-11-15T05:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEventId,
            'originalStart'    => '2016-11-15T01:00:00+00:00',
            'attendees'        => [],
        ];
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        // Step 3. Get events of added user via API and verify result is expected
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                        'start'       => '2016-11-13T01:00:00+00:00',
                        'end'         => '2016-11-19T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_2', 'foo_user_2_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);

        $recurringEvent = $this->getEntity(CalendarEvent::class, $recurringEventId)
            ->getChildEvents()
            ->getValues();
        $recurringEvent = $recurringEvent[0];
        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
                'createdAt'   => $recurringEvent->getAttendeeByEmail('foo_user_1@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt'   => $recurringEvent->getAttendeeByEmail('foo_user_1@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
                'createdAt'   => $recurringEvent->getAttendeeByEmail('foo_user_2@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt'   => $recurringEvent->getAttendeeByEmail('foo_user_2@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ],
        ];

        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-11-14T01:00:00+00:00',
                'end'              => '2016-11-14T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-11-16T01:00:00+00:00',
                'end'              => '2016-11-16T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }
}
