<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers mass delete action for recurring calendar event on its grid.
 *
 * Use cases covered:
 * - Mass delete action deletes regular event and cancels exception event.
 * - Mass delete action deletes recurring event with exceptions.
 *
 * @dbIsolationPerTest
 */
class RecurringCalendarEventMassDeleteTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Mass delete action deletes regular event and cancels exception event.
     *
     * Steps:
     *
     * 1. Create new recurring event with 2 attendees.
     * 2. Create exception for the recurring event with updated title and time.
     * 3. Create new regular calendar event with same attendees.
     * 4. Execute delete mass action for regular event and exception event.
     * 5. Get events via API and check the removed events are not exist.
     * 6. Check number of calendar events and attendees in the system after all manipulations.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMassDeleteActionDeletesRegularEventAndCancelsExceptionEvent()
    {
        // Step 1. Create new recurring event without attendees.
        // Recurring event with occurrences: 2016-04-25, 2016-05-08, 2016-05-09, 2016-05-22
        $eventData = [
            'title'           => 'Test Recurring Event',
            'description'     => 'Test Recurring Event Description',
            'allDay'          => false,
            'calendar'        => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'           => '2016-04-25T01:00:00+00:00',
            'end'             => '2016-04-25T02:00:00+00:00',
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Model\Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Model\Recurrence::DAY_SUNDAY, Model\Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ],
            'attendees'       => [
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
            ]
        ];

        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception for the recurring event with updated title and time.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'            => 'Test Recurring Event Changed',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-22T03:00:00+00:00',
                        'end'              => '2016-05-22T05:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-22T01:00:00+00:00',
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
                        ]
                    ],
                    JSON_THROW_ON_ERROR
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create new regular calendar event with same attendees.
        $eventData = [
            'title'           => 'Test Simple Event',
            'description'     => 'Test Simple Event Description',
            'allDay'          => false,
            'calendar'        => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'           => '2016-04-27T01:00:00+00:00',
            'end'             => '2016-04-27T02:00:00+00:00',
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
            'attendees'       => [
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
            ]
        ];

        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $recurringEvent */
        $regularEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Execute delete mass action for regular event.
        $this->client->disableReboot();
        $url = $this->getUrl(
            'oro_datagrid_mass_action',
            [
                'gridName'   => 'calendar-event-grid',
                'actionName' => 'delete',
                'inset'      => 1,
                'values'     => implode(',', [$regularEvent->getId()])
            ]
        );
        $this->ajaxRequest(
            'DELETE',
            $url,
            [],
            [],
            $this->generateBasicAuthHeader(
                'foo_user_1',
                'password',
                $this->getReference('oro_calendar:user:foo_user_1')->getOrganization()->getId()
            )
        );
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['successful'] === true);
        $this->assertTrue($data['count'] === 1);

        // Step 5. Get events via API and check the removed events are not exist.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'       => '2016-04-01T01:00:00+00:00',
                        'end'         => '2016-06-01T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $expectedAttendees = [
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
        ];

        $expectedResponse = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
                'allDay'      => false,
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
                'allDay'      => false,
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'start'       => '2016-05-09T01:00:00+00:00',
                'end'         => '2016-05-09T02:00:00+00:00',
                'allDay'      => false,
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'start'       => '2016-05-22T03:00:00+00:00',
                'end'         => '2016-05-22T05:00:00+00:00',
                'allDay'      => false,
                'attendees'   => $expectedAttendees,
            ]
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 6. Check number of calendar events and attendees in the system after all manipulations.
        $this->getEntityManager()->clear();

        $this->assertCount(
            4,
            $this->getEntityRepository(CalendarEvent::class)->findAll(),
            'Failed asserting 2 events exist in the persistence: ' . PHP_EOL .
            '1 - recurring event' . PHP_EOL .
            '2 - child of event 1' . PHP_EOL .
            '3 - cancelled exception event of 1' . PHP_EOL .
            '4 - child of event 3' . PHP_EOL
        );

        $this->assertCount(
            1,
            $this->getEntityRepository(Entity\Recurrence::class)->findAll(),
            'Failed asserting 1 recurrence entity exist in the persistence: ' . PHP_EOL .
            '1 - recurrence of event 1' . PHP_EOL
        );

        $this->assertCount(
            4,
            $this->getEntityRepository(Attendee::class)->findAll(),
            'Failed asserting 3 attendees exist in the persistence: ' . PHP_EOL .
            '1 - 1st attendee of event 1' . PHP_EOL .
            '2 - 2nd attendee of event 1' . PHP_EOL .
            '3 - 1nd attendee of event 3' . PHP_EOL .
            '4 - 2nd attendee of event 3' . PHP_EOL
        );
    }

    /**
     * Mass delete action deletes recurring event with exceptions.
     *
     * Steps:
     * 1. Create recurring calendar event with 2 attendees.
     * 2. Create exception event with one more attendee.
     * 3. Create exception event with no attendees.
     * 4. Execute delete mass action for recurring event.
     * 5. Check no events exist in calendars of all attendees.
     * 6. Check no records exist in the persistence after all manipulations.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMassDeleteActionDeletesRecurringEventWithExceptions()
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
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
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
            'start'            => '2016-04-03T01:00:00+00:00',
            'end'              => '2016-04-03T02:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-03T01:00:00+00:00',
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
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 201);

        // Step 3. Create exception event with no attendees.
        $exceptionData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-04T01:00:00+00:00',
            'end'              => '2016-04-04T02:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-04T01:00:00+00:00',
            'attendees'        => [],
        ];
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 201);

        // Step 4. Execute delete mass action for recurring event.
        $this->client->disableReboot();
        $url = $this->getUrl(
            'oro_datagrid_mass_action',
            [
                'gridName'   => 'calendar-event-grid',
                'actionName' => 'delete',
                'inset'      => 1,
                'values'     => implode(',', [$recurringEvent->getId()])
            ]
        );
        $this->ajaxRequest(
            'DELETE',
            $url,
            [],
            [],
            $this->generateBasicAuthHeader(
                'foo_user_1',
                'password',
                $this->getReference('oro_calendar:user:foo_user_1')->getOrganization()->getId()
            )
        );
        $result = $this->client->getResponse();
        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['successful']);
        $this->assertSame(1, $data['count']);

        // Step 5. Check no events exist in calendars of all attendees.

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

        // Step 6. Check no records exist in the persistence after all manipulations.
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
