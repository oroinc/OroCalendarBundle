<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers recurring event exceptions clear logic.
 *
 * Use cases covered:
 * - Update recurring event recurrence clears exceptions when "updateExceptions"=true.
 * - Update recurring event recurrence doesn't clear exceptions when "updateExceptions"=false.
 * - Update recurring event without recurrence change doesn't change exceptions when "updateExceptions"=true.
 * - Update recurring event "start" and "end" clears exceptions when "updateExceptions"=true.
 * - Remove recurring event recurrence clears exceptions when "updateExceptions"=true.
 * - Remove recurring event recurrence doesn't clear exceptions when "updateExceptions"=false.
 * - Remove recurring event recurrence clears exceptions when "updateExceptions"=true and "notifyAttendees"=all
 *   and when recurring event has attendees but one of the exceptions doesn't have attendees.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 *
 * @dbIsolationPerTest
 */
class RecurringEventRecurrenceUpdateExceptionTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * @param $response
     * @return array
     */
    private function getResponseArray($response)
    {
        return [
            'uid'                       => $response['uid'],
            'invitationStatus'          => Attendee::STATUS_NONE,
            'editableInvitationStatus'  => false,
            'organizerDisplayName'      => 'Billy Wilf',
            'organizerEmail'            => 'foo_user_1@example.com',
            'organizerUserId'           => $response['organizerUserId']
        ];
    }

    /**
     * @param $response
     * @return array
     */
    private function getAcceptedResponseArray($response)
    {
        return [
            'uid'                       => $response['uid'],
            'invitationStatus'          => Attendee::STATUS_ACCEPTED,
            'editableInvitationStatus'  => true,
            'organizerDisplayName'      => 'Billy Wilf',
            'organizerEmail'            => 'foo_user_1@example.com',
            'organizerUserId'           => $response['organizerUserId']
        ];
    }

    /**
     * Update recurring event recurrence clears exceptions when "updateExceptions"=true.
     *
     * Step:
     * 1. Create new recurring event without attendees.
     * 2, Create first exception with cancelled flag for the recurring event.
     * 3. Create another exception for the recurring event with different title, description and start time.
     * 4. Check the events exposed in the API without cancelled exception and with modified second exception.
     * 5. Update Recurrence for recurring with updateExceptions flag === true.
     * 6. Check exceptional event was removed.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRecurringEventRecurrenceClearsExceptionsWhenUpdateExceptionsIsTrue()
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
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ],
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create first exception with cancelled flag for the recurring event.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'isCancelled'      => true,
                        'title'            => 'Test Recurring Event',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-09T01:00:00+00:00',
                        'end'              => '2016-05-09T02:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-09T01:00:00+00:00',
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
        /** @var CalendarEvent $cancelledEventException */
        $cancelledEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create another exception for the recurring event with different title, description and start time.
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
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Check the events exposed in the API without cancelled exception and with modified second exception.
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

        $responseWithExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'start'            => '2016-05-22T03:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        // Step 5. Update Recurrence for recurring with updateExceptions flag === true
        $changedEventData = $eventData;
        $changedEventData['recurrence'] = [
            'timeZone'       => 'UTC',
            'recurrenceType' => Recurrence::TYPE_WEEKLY,
            'interval'       => 2,
            'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
            'startTime'      => '2016-04-25T01:00:00+00:00',
            'occurrences'    => 3,
            'endTime'        => null,
        ];
        $changedEventData['updateExceptions'] = true;
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($changedEventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 6. Check exceptional event was removed.
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

        $responseWithClearedExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'isCancelled' => false,
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-09T01:00:00+00:00',
                'end'         => '2016-05-09T02:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithClearedExceptions, $response, false);

        $this->getEntityManager()->clear();
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
            'Failed asseting exception is removed when cleared.'
        );
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asseting exception is removed when cleared.'
        );
    }

    /**
     * Update recurring event recurrence doesn't clear exceptions when "updateExceptions"=false.
     *
     * Step:
     * 1. Create new recurring event without attendees.
     * 2. Create first exception with cancelled flag for the recurring event.
     * 3. Create another exception for the recurring event with different title, description and start time.
     * 4. Check the events exposed in the API without cancelled exception and with modified second exception.
     * 5. Update Recurrence for recurring event with updateExceptions flag === false.
     * 6. Check exceptional event presented.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRecurringEventRecurrenceDoesNotClearExceptionsWhenUpdateExceptionsIsFalse()
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
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ],
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create first exception with cancelled flag for the recurring event.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'isCancelled'      => true,
                        'title'            => 'Test Recurring Event',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-09T01:00:00+00:00',
                        'end'              => '2016-05-09T02:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-09T01:00:00+00:00',
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
        /** @var CalendarEvent $cancelledEventException */
        $cancelledEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create another exception for the recurring event with different title, description and start time.
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
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Check the events exposed in the API without cancelled exception and with modified second exception.
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

        $responseWithExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'start'            => '2016-05-22T03:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        // Step 5. Update Recurrence for recurring event with updateExceptions flag === false.
        $changedEventData = $eventData;
        $changedEventData['recurrence'] = [
            'timeZone'       => 'UTC',
            'recurrenceType' => Recurrence::TYPE_WEEKLY,
            'interval'       => 2,
            'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
            'startTime'      => '2016-04-25T01:00:00+00:00',
            'occurrences'    => 3,
            'endTime'        => null,
        ];
        $changedEventData['updateExceptions'] = false;
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($changedEventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 6. Check exceptional event presented.
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
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        $this->getEntityManager()->clear();
        $this->assertNotNull(
            $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
            'Failed asserting exception is not removed when not cleared.'
        );
        $this->assertNotNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asserting exception is not removed when not cleared.'
        );
    }

    /**
     * Update recurring event without recurrence change doesn't change exceptions when "updateExceptions"=true.
     *
     * Step:
     * 1. Create new recurring event without attendees.
     * 2, Create first exception with cancelled flag for the recurring event.
     * 3. Create another exception for the recurring event with different title, description and start time.
     * 4. Check the events exposed in the API without cancelled exception and with modified second exception.
     * 5. Update recurring event but do not update recurrence pattern updateExceptions flag === true
     * 6. Check exceptional event was not removed
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRecurringEventWithoutRecurrenceChangeDoesNotChangeExceptionsWhenUpdateExceptionsIsTrue()
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
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ],
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create first exception with cancelled flag for the recurring event.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'isCancelled'      => true,
                        'title'            => 'Test Recurring Event',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-09T01:00:00+00:00',
                        'end'              => '2016-05-09T02:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-09T01:00:00+00:00',
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
        /** @var CalendarEvent $cancelledEventException */
        $cancelledEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create another exception for the recurring event with different title, description and start time.
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
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Check the events exposed in the API without cancelled exception and with modified second exception.
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

        $responseWithExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'start'            => '2016-05-22T03:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        // Step 5. Update recurring event but do not update recurrence pattern updateExceptions flag === true
        $changedEventData = $eventData;
        $changedEventData['start'] = '2016-04-25T01:00:00+00:00';
        $changedEventData['end'] = '2016-04-25T02:00:00+00:00';
        $changedEventData['updateExceptions'] = true;
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($changedEventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 6. Check exceptional event was not removed
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
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        $this->getEntityManager()->clear();
        $this->assertNotNull(
            $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
            'Failed asserting exception is not removed when not cleared.'
        );
        $this->assertNotNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asserting exception is not removed when not cleared.'
        );
    }

    /**
     * Update recurring event "start" and "end" clears exceptions when "updateExceptions"=true.
     *
     * Step:
     * 1. Create new recurring event without attendees.
     * 2, Create first exception with cancelled flag for the recurring event.
     * 3. Create another exception for the recurring event with different title, description and start time.
     * 4. Check the events exposed in the API without cancelled exception and with modified second exception.
     * 5. Update recurring event change start and end dates, updateExceptions flag === true
     * 6. Check exceptional event was removed.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRecurringEventStartEndDatesClearsExceptionsWhenUpdateExceptionsIsTrue()
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
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ],
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create first exception with cancelled flag for the recurring event.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'isCancelled'      => true,
                        'title'            => 'Test Recurring Event',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-09T01:00:00+00:00',
                        'end'              => '2016-05-09T02:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-09T01:00:00+00:00',
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
        /** @var CalendarEvent $cancelledEventException */
        $cancelledEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create another exception for the recurring event with different title, description and start time.
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
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Check the events exposed in the API without cancelled exception and with modified second exception.
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

        $responseWithExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'start'            => '2016-05-22T03:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        // Step 5. Update recurring event change start and end dates, updateExceptions flag === true
        $changedEventData = $eventData;
        $changedEventData['start'] = '2016-04-25T05:00:00+00:00';
        $changedEventData['end'] = '2016-04-25T06:00:00+00:00';
        $changedEventData['updateExceptions'] = true;
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($changedEventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 6. Check exceptional event was removed.
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
        $responseWithClearedExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T05:00:00+00:00',
                'end'         => '2016-04-25T06:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T05:00:00+00:00',
                'end'         => '2016-05-08T06:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'isCancelled' => false,
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-09T05:00:00+00:00',
                'end'         => '2016-05-09T06:00:00+00:00',
            ],

            [
                'id'          => $recurringEvent->getId(),
                'isCancelled' => false,
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-22T05:00:00+00:00',
                'end'         => '2016-05-22T06:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithClearedExceptions, $response, false);

        $this->getEntityManager()->clear();
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
            'Failed asserting exception is removed when cleared.'
        );
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asserting exception is removed when cleared.'
        );
    }

    /**
     * Remove recurring event recurrence clears exceptions when "updateExceptions"=true.
     *
     * Step:
     * 1. Create new recurring event without attendees.
     * 2. Create first exception with cancelled flag for the recurring event.
     * 3. Create another exception for the recurring event with different title, description and start time.
     * 4. Check the events exposed in the API without cancelled exception and with modified second exception.
     * 5. Update recurring event, remove recurrence, updateExceptions flag === true.
     * 6. Check exceptional event was removed and occurrences were removed.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRemoveRecurringEventRecurrenceClearsExceptionsWhenUpdateExceptionsIsTrue()
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
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ],
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create first exception with cancelled flag for the recurring event.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'isCancelled'      => true,
                        'title'            => 'Test Recurring Event',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-09T01:00:00+00:00',
                        'end'              => '2016-05-09T02:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-09T01:00:00+00:00',
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
        /** @var CalendarEvent $cancelledEventException */
        $cancelledEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create another exception for the recurring event with different title, description and start time.
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
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Check the events exposed in the API without cancelled exception and with modified second exception.
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

        $responseWithExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'start'            => '2016-05-22T03:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        // Step 5. Update recurring event, remove recurrence, updateExceptions flag === true.
        $changedEventData = $eventData;
        $changedEventData['recurrence'] = null;
        $changedEventData['updateExceptions'] = true;
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($changedEventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 6. Check exceptional event was removed and occurrences were removed.
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
        $responseWithNoRecurringEvents = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithNoRecurringEvents, $response, false);

        $this->getEntityManager()->clear();
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
            'Failed asserting exception is removed when cleared.'
        );
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asserting exception is removed when cleared.'
        );
    }

    /**
     * Remove recurring event recurrence doesn't clear exceptions when "updateExceptions"=false.
     *
     * Step:
     * 1. Create new recurring event without attendees.
     * 2, Create first exception with cancelled flag for the recurring event.
     * 3. Create another exception for the recurring event with different title, description and start time.
     * 4. Check the events exposed in the API without cancelled exception and with modified second exception.
     * 5. Update recurring event remove recurrence, updateExceptions flag === false
     * 6. Check exceptional event was not removed but occurrences were removed.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRemoveRecurringEventRecurrenceDoesntClearExceptionsWhenUpdateExceptionIsFalse()
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
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval'       => 2,
                'dayOfWeek'      => [Recurrence::DAY_SUNDAY, Recurrence::DAY_MONDAY],
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 4,
                'endTime'        => '2016-06-10T01:00:00+00:00',
            ],
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create first exception with cancelled flag for the recurring event.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'isCancelled'      => true,
                        'title'            => 'Test Recurring Event',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-05-09T01:00:00+00:00',
                        'end'              => '2016-05-09T02:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-05-09T01:00:00+00:00',
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
        /** @var CalendarEvent $cancelledEventException */
        $cancelledEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create another exception for the recurring event with different title, description and start time.
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
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Check the events exposed in the API without cancelled exception and with modified second exception.
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

        $responseWithExceptions = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-05-08T01:00:00+00:00',
                'end'         => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'start'            => '2016-05-22T03:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithExceptions, $response, false);

        // Step 5. Update recurring event remove recurrence, updateExceptions flag === false
        $changedEventData = $eventData;
        $changedEventData['recurrence'] = null;
        $changedEventData['updateExceptions'] = false;
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($changedEventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 6. Check exceptional event was not removed but occurrences were removed.
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
        $responseWithNoRecurringEvents = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'               => $cancelledEventException->getId(),
                'isCancelled'      => true,
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-09T01:00:00+00:00',
                'start'            => '2016-05-09T01:00:00+00:00',
                'end'              => '2016-05-09T02:00:00+00:00',
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-05-22T01:00:00+00:00',
                'start'            => '2016-05-22T03:00:00+00:00',
                'end'              => '2016-05-22T05:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithNoRecurringEvents, $response, false);

        $this->getEntityManager()->clear();
        $this->assertNotNull(
            $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
            'Failed asserting exception is not removed when not cleared.'
        );
        $this->assertNotNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asserting exception is not removed when not cleared.'
        );
    }

    /**
     * Remove recurring event recurrence clears exceptions when "updateExceptions"=true and "notifyAttendees"=all
     * and when recurring event has attendees but one of the exceptions doesn't have attendees.
     *
     * Step:
     * 1. Create new recurring event with attendees.
     * 2. Create exception by removing all attendees.
     * 3. Check the events exposed in the API with removed attendees in the exception.
     * 4. Update recurring event and remove recurrence with "updateExceptions"=true and "notifyAttendees"=all.
     * 5. Check exceptional event was removed and occurrences were removed.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRemoveRecurrenceClearsExceptionWithoutAttendeesWhenUpdateExceptionsAndNotifyIsTrue()
    {
        // Step 1. Create new recurring event with attendees.
        // Recurring event with occurrences: 2016-04-25, 2016-04-26, 2016-04-27
        $eventData = [
            'title'           => 'Test Recurring Event',
            'description'     => 'Test Recurring Event Description',
            'allDay'          => false,
            'calendar'        => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'           => '2016-04-25T01:00:00+00:00',
            'end'             => '2016-04-25T02:00:00+00:00',
            'recurrence'      => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-25T01:00:00+00:00',
                'occurrences'    => 3
            ],
            'attendees'       => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email'       => 'foo_user_3@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                    'email'       => 'foo_user_2@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ],
            ],
            'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        $this->assertResponseEquals(
            [
                'id'                        => $response['id'],
                'uid'                       => $response['uid'],
                'invitationStatus'          => Attendee::STATUS_ACCEPTED,
                'editableInvitationStatus'  => true,
                'organizerDisplayName'      => 'Billy Wilf',
                'organizerEmail'            => 'foo_user_1@example.com',
                'organizerUserId'           => $response['organizerUserId']
            ],
            $response
        );

        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception by removing all attendees.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'            => 'Test Recurring Event',
                        'description'      => 'Test Recurring Event Description',
                        'allDay'           => false,
                        'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'            => '2016-04-26T01:00:00+00:00',
                        'end'              => '2016-04-26T02:00:00+00:00',
                        'recurringEventId' => $recurringEvent->getId(),
                        'originalStart'    => '2016-04-26T01:00:00+00:00',
                        'attendees'        => [],
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
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Check the events exposed in the API with removed attendees in the exception.
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
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResponse = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'               => $changedEventException->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-04-26T01:00:00+00:00',
                'start'            => '2016-04-26T01:00:00+00:00',
                'end'              => '2016-04-26T02:00:00+00:00',
                'attendees'        => [],
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-27T01:00:00+00:00',
                'end'         => '2016-04-27T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];
        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 4. Update recurring event and remove recurrence with "updateExceptions"=true
        // and "notifyAttendees"=all.
        $changedEventData = $eventData;
        $changedEventData['recurrence'] = null;
        $changedEventData['updateExceptions'] = true;
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($changedEventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 5. Check exceptional event was removed and occurrences were removed.
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

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );
        $responseWithNoRecurringEvents = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-25T01:00:00+00:00',
                'end'         => '2016-04-25T02:00:00+00:00',
            ],
        ];
        $this->assertResponseEquals($responseWithNoRecurringEvents, $response, false);

        $this->getEntityManager()->clear();
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asserting exception is removed when cleared.'
        );
        $this->assertNull(
            $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
            'Failed asserting exception is removed when cleared.'
        );
    }
}
