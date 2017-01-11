<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers recurring events delete operations.
 *
 * Use cases covered:
 * - Delete recurring event with exceptions removes all events and attendees.
 *
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RecurringEventDeleteTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);  // force load fixtures
    }

    /**
     * Delete recurring event with exceptions removes all events and attendees.
     *
     * Steps:
     * 1. Create recurring calendar event with 2 attendees.
     * 2. Create exception event with one more attendee.
     * 3. Create exception event with no attendees.
     * 4. Create cancelled exception.
     * 5. Delete recurring event with flag "notifyAttendees"="all".
     * 6. Check no events exist in calendars of all attendees.
     * 7. Check no records exist in the persistence after all manipulations.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDeleteRecurringEventWithExceptionsRemovesAllEventsAndAttendees()
    {
        // Step 1. Create recurring calendar event with 2 attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-01T01:00:00+00:00',
            'end'              => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor'  => '#FF0000',
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
            'attendees'        => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                    'email'       => 'foo_user_2@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Model\Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ]
        ];
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception event with one more attendee.
        $exceptionData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-02T01:00:00+00:00',
            'end'              => '2016-04-02T02:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-02T01:00:00+00:00',
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
            'attendees'        => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                    'email'       => 'foo_user_2@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email'       => 'foo_user_3@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
        ];
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 201);

        // Step 3. Create exception event with no attendees.
        $exceptionData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-03T01:00:00+00:00',
            'end'              => '2016-04-03T02:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-03T01:00:00+00:00',
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
            'attendees'        => [],
        ];
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 201);

        // Step 4. Create cancelled exception.
        $exceptionData = [
            'isCancelled'      => true,
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-04T01:00:00+00:00',
            'end'              => '2016-04-04T02:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-02T01:00:00+00:00',
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
            'attendees'        => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                    'email'       => 'foo_user_2@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
        ];
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 201);

        // Step 5. Delete recurring event with flag "notifyAttendees"="all".
        $this->restRequest(
            [
                'method'  => 'DELETE',
                'url'     => $this->getUrl(
                    'oro_api_delete_calendarevent',
                    [
                        'id' => $recurringEvent->getId(),
                        'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
                    ]
                ),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // Step 6. Check no events exist in calendars of all attendees.

        // Check events of owner of the event
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'       => '2016-04-01T01:00:00+00:00',
                        'end'         => '2016-04-30T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $expectedResponse = [];

        $this->assertResponseEquals($expectedResponse, $response, true);

        // Check events of second attendee of the event
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                        'start'       => '2016-04-01T01:00:00+00:00',
                        'end'         => '2016-04-30T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_2', 'foo_user_2_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $expectedResponse = [];

        $this->assertResponseEquals($expectedResponse, $response, true);

        // Check events of extra attendee of the exception event
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                        'start'       => '2016-04-01T01:00:00+00:00',
                        'end'         => '2016-04-30T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_3', 'foo_user_3_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $expectedResponse = [];

        $this->assertResponseEquals($expectedResponse, $response, true);

        // Step 7. Check no records exist in the persistence after all manipulations.
        $this->getEntityManager()->clear();

        $this->assertEmpty(
            $this->getEntityRepository(CalendarEvent::class)->findAll(),
            'Failed asserting there are no calendar events in the persistence.'
        );

        $this->assertEmpty(
            $this->getEntityRepository(Entity\Recurrence::class)->findAll(),
            'Failed asserting there are no recurrence entitites in the persistence.'
        );

        $this->assertEmpty(
            $this->getEntityRepository(Entity\Recurrence::class)->findAll(),
            'Failed asserting there are no recurrence entities in the persistence.'
        );
    }
}
