<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers recurring events update operations.
 *
 * Use cases covered:
 * - Exception is updated after update of recurring event.
 * - Event of new attendee in exception event is updated after update of recurring event.
 * - Add same user attendees in recurring event as attendees in exception.
 * - Add exception without attendees in recurring event with attendees.
 * - Add non-user attendee in recurring event adds attendee in exception event.
 * - Add user attendee in recurring event with attendees adds attendee in exception event.
 * - Add user attendee in recurring event without attendees adds attendee in exception event.
 * - Add user attendee in recurring event when the attendee was previously removed from exception event.
 * - Add user attendee in exception event when the attendee was previously removed from exception event.
 * - Replace non-user attendee in recurring event replaces the attendee in exception event.
 * - Non-user attendee removed from recurring event removed from exception event.
 * - User attendee removed from recurring event removed from exception event.
 * - User attendee removed from recurring event does not removed from exception event with custom attendees.
 * - User attendee removed from exception event.
 * - Exception event with overridden attendees not added on calendar of new attendee added to recurring event.
 *
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RecurringEventUpdateExceptionTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    private function getResponseArray(array $response): array
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

    private function getAcceptedResponseArray(array $response): array
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
     * Exception is updated after update of recurring event.
     *
     * Steps:
     * 1. Create recurring event with 2 attendees.
     * 2. Create exception for the recurring event with changed values of attributes.
     * 3. Change recurring event.
     * 4. Check attributes of all events updated, check attributes of exception event are updated.
     *
     * @dataProvider updateExceptionsDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExceptionIsUpdatedAfterUpdateOfRecurringEvent(
        array $changedEventData,
        array $exceptionChangedData
    ) {
        // Step 1. Create recurring event with 2 attendees.
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
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email'       => 'foo_user_3@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
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

        // Step 2. Create exception for the recurring event with changed values of attributes.
        $exceptionData = array_replace_recursive(
            [
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T05:00:00+00:00',
                'backgroundColor'  => '#FF0000',
                'recurringEventId' => $recurringEvent->getId(),
                'originalStart'    => '2016-04-04T01:00:00+00:00',
                'attendees'        => [
                    [
                        'displayName' => 'External Attendee',
                        'email'       => 'ext@example.com',
                        'status'      => Attendee::STATUS_NONE,
                        'type'        => Attendee::TYPE_OPTIONAL,
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                        'email'       => 'foo_user_3@example.com',
                        'status'      => Attendee::STATUS_ACCEPTED,
                        'type'        => Attendee::TYPE_REQUIRED,
                    ]
                ],
            ],
            $exceptionChangedData
        );
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Change recurring event.
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(array_replace_recursive($eventData, $changedEventData), JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 4. Check attributes of all events updated, check attributes of exception event are updated.
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
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $expectedAttendees = [
            [
                'displayName' => 'External Attendee',
                'email'       => 'ext@example.com',
                'userId'      => null,
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ]
        ];
        $expectedResponse = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ]
        ];
        $exceptionData['id'] = $exceptionEvent->getId();
        $exceptionData['attendees'] = $expectedAttendees;
        $expectedResponse[] = $exceptionData;
        foreach ($expectedResponse as &$item) {
            $item = array_replace_recursive($item, $changedEventData);
            if (!empty($item['recurringEventId'])) {
                $item = array_replace_recursive($item, $exceptionChangedData);
            }
        }
        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    public function updateExceptionsDataProvider(): array
    {
        return [
            'All simple attributes should be changed, but title'           => [
                'changedEventData'     => [
                    'title'           => 'New Test Recurring Event Title',
                    'description'     => 'New Description',
                    'allDay'          => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'title' => 'Test Recurring Event Changed'
                ]
            ],
            'All simple attributes should be changed, but description'     => [
                'changedEventData'     => [
                    'title'           => 'New Test Recurring Event Title',
                    'description'     => 'New Description',
                    'allDay'          => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'description' => 'Test Recurring Event Description Changed',
                ]
            ],
            'All simple attributes should be changed, but allDay'          => [
                'changedEventData'     => [
                    'title'           => 'New Test Recurring Event Title',
                    'description'     => 'New Description',
                    'allDay'          => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'allDay' => true,
                ]
            ],
            'All simple attributes should be changed, but backgroundColor' => [
                'changedEventData'     => [
                    'title'           => 'New Test Recurring Event Title',
                    'description'     => 'New Description',
                    'allDay'          => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'backgroundColor' => '#FF00FF',
                ]
            ],
        ];
    }

    /**
     * Event of new attendee in exception event is updated after update of recurring event.
     *
     * Steps:
     * 1. Create recurring calendar event with 2 attendees.
     * 2. Create exception event with one more attendee.
     * 3. Change recurring event title and description.
     * 4. Check new attendee event titles was updated.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testEventOfNewAttendeeInExceptionEventIsUpdatedAfterUpdateOfRecurringEvent()
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
                'recurrenceType' => Recurrence::TYPE_DAILY,
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
            'start'            => '2016-04-04T04:00:00+00:00',
            'end'              => '2016-04-04T05:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-04T01:00:00+00:00',
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Change recurring event title and description.
        $eventData['title'] = 'Updated Title';
        $eventData['description'] = 'Updated Description';
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 4. Check new attendee event titles was updated.
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

        $exceptionEvent = $this->getEntity(CalendarEvent::class, $exceptionEvent->getId());
        $attendeeExceptionCalendarEvent = $exceptionEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_3')
        );
        $expectedResponse = [
            [
                'id'            => $attendeeExceptionCalendarEvent->getId(),
                'title'         => 'Updated Title',
                'description'   => 'Updated Description',
                'allDay'        => false,
                'calendar'      => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'         => '2016-04-04T04:00:00+00:00',
                'end'           => '2016-04-04T05:00:00+00:00',
                'originalStart' => '2016-04-04T01:00:00+00:00',
                'attendees'     => [
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                        'email'       => 'foo_user_1@example.com',
                        'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                        'status'      => Attendee::STATUS_ACCEPTED,
                        'type'        => Attendee::TYPE_REQUIRED,
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                        'email'       => 'foo_user_3@example.com',
                        'status'      => Attendee::STATUS_ACCEPTED,
                        'type'        => Attendee::TYPE_REQUIRED,
                        'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                        'email'       => 'foo_user_2@example.com',
                        'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                        'status'      => Attendee::STATUS_ACCEPTED,
                        'type'        => Attendee::TYPE_REQUIRED,
                    ],
                ],
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Add same user attendees in recurring event as attendees in exception.
     *
     * Steps:
     * 1. Create new recurring event without attendees.
     * 2. Create exception for the recurring event with changed title, description and start time.
     * 3. Change recurring event with the same attendees as in exception.
     * 4. Check the attendees list is the same in all events of recurring event including the exception.
     * 5. Check events in attendee's calendar match events in owner calendar.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddSameUserAttendeesToRecurringEventAsAttendeesInException()
    {
        // Step 1. Create new recurring event without attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03
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
            'attendees'        => [],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 3
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

        // Step 2. Create exception for the recurring event with changed title, description and start time.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-03T03:00:00+00:00',
            'end'              => '2016-04-03T05:00:00+00:00',
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Change recurring event with the same attendees as in exception.
        $eventData['attendees'] = [
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
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 4. Check the attendees list is the same in all events of recurring event including the exception.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'       => '2016-04-01T00:00:00+00:00',
                        'end'         => '2016-04-30T00:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T05:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check events in attendee's calendar match events in owner calendar.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                        'start'       => '2016-04-01T00:00:00+00:00',
                        'end'         => '2016-04-30T00:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);

        /** @var CalendarEvent $attendeeRecurringEvent */
        $attendeeRecurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId())
            ->getChildEvents()
            ->first();

        /** @var CalendarEvent $attendeeExceptionEvent */
        $attendeeExceptionEvent = $this->getEntity(CalendarEvent::class, $exceptionEvent->getId())
            ->getChildEvents()
            ->first();

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeExceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T05:00:00+00:00',
                'attendees'   => $expectedAttendees
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Add exception without attendees in recurring event with attendees.
     *
     * Steps:
     * 1. Create new recurring event with attendees.
     * 2. Create exception for the recurring event without attendees.
     * 3. Check all events shown in the calendar of owner user.
     * 4. Check all events except exception shown in the calendar of attendee user.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddExceptionWithoutAttendeesInRecurringEventWithAttendees()
    {
        // Step 1. Create new recurring event with attendees.
        // Recurring event with occurrences: 2016-11-14, 2016-11-15, 2016-11-16
        $eventData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-11-14T01:00:00+00:00',
            'end'              => '2016-11-14T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor'  => '#FF0000',
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
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-11-14T01:00:00+00:00',
                'occurrences'    => 3
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception for the recurring event without attendees.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-11-15T03:00:00+00:00',
            'end'              => '2016-11-15T05:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-11-15T01:00:00+00:00',
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Check all events shown in the calendar of owner user.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'       => '2016-11-13T01:00:00+00:00',
                        'end'         => '2016-11-19T01:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
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
                'start'       => '2016-11-14T01:00:00+00:00',
                'end'         => '2016-11-14T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-11-15T03:00:00+00:00',
                'end'         => '2016-11-15T05:00:00+00:00',
                'attendees'   => [],
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-11-16T01:00:00+00:00',
                'end'         => '2016-11-16T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 4. Check all events except exception shown in the calendar of attendee user.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
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

        /** @var CalendarEvent $attendeeRecurringEvent */
        $attendeeRecurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId())
            ->getChildEvents()
            ->first();

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-11-14T01:00:00+00:00',
                'end'         => '2016-11-14T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-11-16T01:00:00+00:00',
                'end'         => '2016-11-16T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Add non-user attendee in recurring event adds attendee in exception event.
     *
     * Steps:
     * 1. Create recurring event with 2 attendees.
     * 2. Add exception with updated title, description and time.
     * 3. Update recurring event and add one more attendee not related to user.
     * 4. Check the list of attendees is the same in each occurrence of recurring event including the exception.
     * 5. Check calendar events of attendee matches events of calendar event owner.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddNonUserAttendeeToRecurringEventAddsAttendeeToExceptionEvent()
    {
        // Step 1. Create recurring event with 2 attendees.
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
            'attendees'        => [
                [
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email'       => 'foo_user_3@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Add exception with updated title, description and time.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-04T03:00:00+00:00',
            'end'              => '2016-04-04T04:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-04T01:00:00+00:00',
            'attendees'        => [
                [
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Update recurring event and add one more attendee not related to user.
        $eventData['attendees'] = [
            [
                'displayName' => 'External Attendee',
                'email'       => 'ext@example.com',
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => 'Another External Attendee',
                'email'       => 'another_ext@example.com',
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ]
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 4. Check the list of attendees is the same in each occurrence of recurring event including
        // the exception.
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

        $expectedAttendees = [
            [
                'displayName' => 'Another External Attendee',
                'email'       => 'another_ext@example.com',
                'userId'      => null,
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => 'External Attendee',
                'email'       => 'ext@example.com',
                'userId'      => null,
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check calendar events of attendee matches events of calendar event owner.
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

        /** @var CalendarEvent $attendeeRecurringEvent */
        $attendeeRecurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId())
            ->getChildEvents()
            ->first();

        /** @var CalendarEvent $attendeeEventException */
        $attendeeEventException = $this->getEntity(CalendarEvent::class, $exceptionEvent->getId())
            ->getChildEvents()
            ->first();

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeEventException->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Add user attendee in recurring event with attendees adds attendee in exception event.
     *
     * Steps:
     * 1. Create recurring event with 2 attendees.
     * 2. Add exception with updated title, description and time.
     * 3. Update recurring event and add one more attendee.
     * 4. Check the list of attendees is the same in each occurrence of recurring event including the exception.
     * 5. Check calendar events of old attendee matches events of calendar event owner.
     * 6. Check calendar events of new attendee matches events of calendar event owner.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddUserAttendeeInRecurringEvenWithAttendeesAddsAttendeeInExceptionEvent()
    {
        // Step 1. Create recurring event with 2 attendees.
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
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Add exception with updated title, description and time.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-04T03:00:00+00:00',
            'end'              => '2016-04-04T04:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-04T01:00:00+00:00',
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
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $recurringEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Update recurring event and add one more attendee.
        $eventData['attendees'] = [
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
            ],
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 4. Check the list of attendees is the same in each occurrence of recurring event including the exception
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check calendar events of old attendee matches events of calendar event owner.
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

        /** @var CalendarEvent $oldAttendeeRecurringEvent */
        $oldAttendeeRecurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId())
            ->getChildEventByCalendar($this->getReference('oro_calendar:calendar:foo_user_2'));

        /** @var CalendarEvent $oldAttendeeEventException */
        $oldAttendeeEventException = $this->getEntity(CalendarEvent::class, $exceptionEvent->getId())
            ->getChildEventByCalendar($this->getReference('oro_calendar:calendar:foo_user_2'));

        $expectedResponse = [
            [
                'id'          => $oldAttendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $oldAttendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $oldAttendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $oldAttendeeEventException->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // 6. Check calendar events of new attendee matches events of calendar event owner.
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

        /** @var CalendarEvent $newAttendeeRecurringEvent */
        $newAttendeeRecurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId())
            ->getChildEventByCalendar($this->getReference('oro_calendar:calendar:foo_user_3'));

        /** @var CalendarEvent $newAttendeeEventException */
        $newAttendeeEventException = $this->getEntity(CalendarEvent::class, $exceptionEvent->getId())
            ->getChildEventByCalendar($this->getReference('oro_calendar:calendar:foo_user_3'));

        $expectedResponse = [
            [
                'id'          => $newAttendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $newAttendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $newAttendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $newAttendeeEventException->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Add user attendee in recurring event without attendees adds attendee in exception event.
     *
     * The test covers next use case:
     * 1. Create recurring calendar event without attendees.
     * 2. Create exception event with custom title, time, description.
     * 3. Add 2 attendees to all recurring events.
     * 4. Check events in calendar of owner user.
     * 5. Check events in calendar of attendee user.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddUserAttendeeInRecurringEvenWithoutAttendeesAddsAttendeeInExceptionEvent()
    {
        // Step 1. Create recurring event without attendees.
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
            'attendees'        => [],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception event with custom title, time, description.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-04T03:00:00+00:00',
            'end'              => '2016-04-04T04:00:00+00:00',
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $recurringEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Add 2 attendees to all recurring events.
        $eventData['attendees'] = [
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
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 4. Check events in calendar of owner user.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check events in calendar of attendee user.
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

        $recurringEvent = $this->reloadEntity($recurringEvent);
        $attendeeRecurringEvent = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_2')
        );
        $attendeeExceptionEvent = $attendeeRecurringEvent->getRecurringEventExceptions()->first();
        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeExceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Add user attendee in recurring event when the attendee was previously removed from exception event.
     *
     * Steps:
     * 1. Create recurring calendar event without attendees.
     * 2. Create exception event with 2 attendees.
     * 3. Remove attendees from exception event.
     * 4. Add 2 attendees to all recurring events.
     * 5. Check events in calendar of owner user.
     * 6. Check events in calendar of attendee user.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddUserAttendeeInRecurringEventWhenTheAttendeeWasPreviouslyRemovedFromExceptionEvent()
    {
        // Step 1. Create recurring calendar event without attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03
        $eventData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-01T01:00:00+00:00',
            'end'              => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor'  => '#FF0000',
            'attendees'        => [],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception event with 2 attendees.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-03T03:00:00+00:00',
            'end'              => '2016-04-03T04:00:00+00:00',
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $recurringEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Remove attendees from exception event.
        $exceptionData['attendees'] = [];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $exceptionEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 4. Add 2 attendees to all recurring events.
        $eventData['attendees'] = [
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
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 5. Check events in calendar of owner user.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-04T01:00:00+00:00',
                'end'         => '2016-04-04T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 6. Check events in calendar of attendee user.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $recurringEvent = $this->reloadEntity($recurringEvent);
        $attendeeCalendarEvent = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_2')
        );
        $attendeeExceptionCalendarEvent = $attendeeCalendarEvent->getRecurringEventExceptions()->first();
        $expectedResponse = [
            [
                'id'          => $attendeeCalendarEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeCalendarEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeExceptionCalendarEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],

            [
                'id'          => $attendeeCalendarEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-04T01:00:00+00:00',
                'end'         => '2016-04-04T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Add user attendee in exception event when the attendee was previously removed from exception event.
     *
     * Steps:
     * 1. Create recurring calendar event with 2 attendees.
     * 2. Create exception event with custom title, time, description and without attendees.
     * 3. Check attendees have no event in the calendar where they were removed.
     * 4. Check exception event for attendee has flag isCancelled=TRUE.
     * 5. Add 2 attendees back to the exception event.
     * 6. Check events in calendar of owner user.
     * 7. Check events in calendar of attendee user.
     * 8. Check exception event for attendee has flag isCancelled=FALSE.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCalendarEventForAttendeeUsersAddedRemovedAndAddedOnSecondTime()
    {
        // Step 1. Create recurring calendar event with 2 attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03
        $eventData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-01T01:00:00+00:00',
            'end'              => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor'  => '#FF0000',
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
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 3,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception event with custom title, time, description and without attendees.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-03T03:00:00+00:00',
            'end'              => '2016-04-03T04:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-03T01:00:00+00:00',
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Check attendees have no event in the calendar where they were removed.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $recurringEvent = $this->reloadEntity($recurringEvent);
        $attendeeRecurringEvent = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_2')
        );

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            // The last event on 2016-04-03 should not be exposed
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 4. Check exception event for attendee has flag isCancelled=TRUE.
        /** @var CalendarEvent $attendeeExceptionEvent */
        $attendeeExceptionEvent = $attendeeRecurringEvent->getRecurringEventExceptions()->first();
        $this->assertTrue(
            $attendeeExceptionEvent->isCancelled(),
            'Failed assert exception event for removed attendee has flag isCancelled=TRUE.'
        );

        // Step 5. Add 2 attendees back to the exception event.
        $exceptionData['attendees'] = [
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
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $exceptionEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 6. Check events in calendar of owner user.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 7. Check events in calendar of attendee user.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeExceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 8. Check exception event for attendee has flag isCancelled=FALSE.
        $attendeeExceptionEvent = $this->reloadEntity($attendeeExceptionEvent);
        $this->assertFalse(
            $attendeeExceptionEvent->isCancelled(),
            'Failed assert exception event for removed attendee has flag isCancelled=TRUE.'
        );
    }

    /**
     * Replace non-user attendee in recurring event replaces the attendee in exception event.
     *
     * Steps:
     * 1. Create new recurring event with 2 attendees.
     * 2. Add exception with updated title, description and time.
     * 3. Update recurring event, remove one of the non-user attendees and add another non-user attendee instead.
     * 4. Check the list of attendees is the same in each occurrence of recurring event including the exception.
     * 5. Check events in calendar of attendee user matches events in calendar of owner user.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testReplaceNonUserAttendeeInRecurringEventReplacesTheAttendeeInExceptionEvent()
    {
        // Step 1. Create new recurring event with 2 attendees.
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
            'attendees'        => [
                [
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email'       => 'foo_user_3@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Add exception with updated title, description and time.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-04T03:00:00+00:00',
            'end'              => '2016-04-04T04:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-04T01:00:00+00:00',
            'attendees'        => [
                [
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Update recurring event, remove one of the attendees and add another attendee instead.
        $eventData['attendees'] = [
            [
                'displayName' => 'Another External Attendee',
                'email'       => 'aext@example.com',
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'status'      => Attendee::STATUS_DECLINED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 4. Check the list of attendees is the same in each occurrence of recurring event including the exception
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

        $expectedAttendees = [
            [
                'displayName' => 'Another External Attendee',
                'email'       => 'aext@example.com',
                'userId'      => null,
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_DECLINED,
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check events in calendar of attendee user matches events in calendar of owner user.
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

        /** @var CalendarEvent $attendeeRecurringEvent */
        $attendeeRecurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId())
            ->getChildEvents()
            ->first();

        /** @var CalendarEvent $attendeeExceptionEvent */
        $attendeeExceptionEvent = $this->getEntity(CalendarEvent::class, $exceptionEvent->getId())
            ->getChildEvents()
            ->first();

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeExceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-04T03:00:00+00:00',
                'end'         => '2016-04-04T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Non-user attendee removed from recurring event removed from exception event.
     *
     * Steps:
     * 1. Create recurring event with 2 attendees (with related user and without related user).
     * 2. Create exception for the recurring event with updated title, description and time.
     * 3. Remove non-user attendee from the recurring event.
     * 4. Check events in calendar of owner user.
     * 5. Check events in calendar of attendee user.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testNonUserAttendeeRemovedFromRecurringEventRemovedFromExceptionEvent()
    {
        // Step 1. Create recurring event with 2 attendees (with related user and without related user).
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03
        $eventData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-01T01:00:00+00:00',
            'end'              => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor'  => '#FF0000',
            'attendees'        => [
                [
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email'       => 'foo_user_3@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 3,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception for the recurring event with updated title, description and time.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-03T03:00:00+00:00',
            'end'              => '2016-04-03T04:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-03T01:00:00+00:00',
            'attendees'        => [
                [
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $recurringEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Remove non-user attendee from the recurring event.
        $eventData['attendees'] = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 4. Check events in calendar of owner user.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $exceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check events in calendar of attendee user.
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

        /** @var CalendarEvent $attendeeRecurringEvent */
        $attendeeRecurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId())
            ->getChildEvents()
            ->first();

        /** @var CalendarEvent $attendeeExceptionEvent */
        $attendeeExceptionEvent = $this->getEntity(CalendarEvent::class, $exceptionEvent->getId())
            ->getChildEvents()
            ->first();

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeExceptionEvent->getId(),
                'title'       => 'Test Recurring Event Changed',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-03T03:00:00+00:00',
                'end'         => '2016-04-03T04:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * User attendee removed from recurring event removed from exception event.
     *
     * Steps:
     * 1. Create recurring calendar event with 2 attendees.
     * 2. Create exception event with updated title.
     * 3. Change recurring event and remove second attendee.
     * 4. Check recurring event has only only 1 attendee in all occurrences.
     * 5. Check number of calendar events and attendees in the system after all manipulations.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUserAttendeeRemovedFromRecurringEventRemovedFromExceptionEvent()
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
            'attendees'        => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_ORGANIZER,
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
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception event with updated title.
        $exceptionData = [
            'title'            => 'Updated Title',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-04T01:00:00+00:00',
            'end'              => '2016-04-04T02:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-04T01:00:00+00:00',
            'attendees'        => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_ORGANIZER,
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
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Change recurring event and remove second attendee.
        $eventData['attendees'] = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_ORGANIZER,
            ]
        ];

        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 4. Check new attendee event titles was updated.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_ORGANIZER,
            ],
        ];

        $expectedResponse = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'            => $exceptionEvent->getId(),
                'title'         => 'Updated Title',
                'description'   => 'Test Recurring Event Description',
                'allDay'        => false,
                'calendar'      => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'         => '2016-04-04T01:00:00+00:00',
                'end'           => '2016-04-04T02:00:00+00:00',
                'originalStart' => '2016-04-04T01:00:00+00:00',
                'attendees'     => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check number of calendar events and attendees in the system after all manipulations.
        $this->getEntityManager()->clear();

        $this->assertCount(
            2,
            $this->getEntityRepository(CalendarEvent::class)->findAll(),
            'Failed asserting 2 events exist in the application: ' . PHP_EOL .
            '1 - recurring event' . PHP_EOL .
            '2 - exception of event 1'
        );

        $this->assertCount(
            2,
            $this->getEntityRepository(Attendee::class)->findAll(),
            'Failed asserting 3 attendees exist in the application: ' . PHP_EOL .
            '1 - attendee of event 1' . PHP_EOL .
            '2 - attendee of event 2'
        );
    }

    /**
     * User attendee removed from recurring event does not removed from exception event with custom attendees.
     *
     * Steps:
     * 1. Create recurring calendar event with 2 attendees.
     * 2. Create exception event and remove organizer attendee.
     * 3. Change recurring event and remove non-organizer attendee.
     * 4. Check recurring event has organizer attendee in all occurrences except exception with second attendee.
     * 5. Check number of calendar events and attendees in the system after all manipulations.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUserAttendeeRemovedFromRecurringEventDoesNotRemovedFromExceptionEventWithCustomAttendees()
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
            'attendees'        => [
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_ORGANIZER,
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
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception event and remove organizer attendee.
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
            'attendees'        => [
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
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Change recurring event and remove non-organizer attendee.
        $eventData['attendees'] = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_ORGANIZER,
            ]
        ];

        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getAcceptedResponseArray($response),
            $response
        );

        // Step 4. Check recurring event has organizer attendee in all occurrences
        //         except exception with second attendee.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                'email'       => 'foo_user_1@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_ORGANIZER,
            ],
        ];

        $expectedExceptionAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'            => $exceptionEvent->getId(),
                'title'         => 'Test Recurring Event',
                'description'   => 'Test Recurring Event Description',
                'allDay'        => false,
                'calendar'      => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'         => '2016-04-04T01:00:00+00:00',
                'end'           => '2016-04-04T02:00:00+00:00',
                'originalStart' => '2016-04-04T01:00:00+00:00',
                'attendees'     => $expectedExceptionAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check number of calendar events and attendees in the system after all manipulations.
        $this->getEntityManager()->clear();

        $this->assertCount(
            3,
            $this->getEntityRepository(CalendarEvent::class)->findAll(),
            'Failed asserting 3 events exist in the application: ' . PHP_EOL .
            '1 - recurring event' . PHP_EOL .
            '2 - exception of event 1' . PHP_EOL .
            '3 - child event of 2'
        );

        $this->assertCount(
            2,
            $this->getEntityRepository(Attendee::class)->findAll(),
            'Failed asserting 3 attendees exist in the application: ' . PHP_EOL .
            '1 - 1st attendee of event 1' . PHP_EOL .
            '2 - 2nd attendee of event 2'
        );
    }

    /**
     * User attendee removed from exception event.
     *
     * Steps:
     * 1. Create recurring calendar event with 2 non-organizer attendees.
     * 2. Create exception event and remove first attendee.
     * 3. Check recurring events in owner user calendar.
     *    There should be 1 attendee in exception event of owner user calendar.
     * 4. Check recurring events in 1st attendee user calendar. There should be no exception event.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUserAttendeeRemovedFromExceptionEvent()
    {
        // Step 1. Create recurring calendar event with 2 non-organizer attendees.
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
            'attendees'        => [
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
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception event and remove first attendee.
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
            'attendees'        => [
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
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        /** @var CalendarEvent $exceptionEvent */
        $exceptionEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Check recurring events in owner user calendar.
        //         There should be 1 attendee in exception event of owner user calendar.
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedExceptionAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
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
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'            => $exceptionEvent->getId(),
                'title'         => 'Test Recurring Event',
                'description'   => 'Test Recurring Event Description',
                'allDay'        => false,
                'calendar'      => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'         => '2016-04-04T01:00:00+00:00',
                'end'           => '2016-04-04T02:00:00+00:00',
                'originalStart' => '2016-04-04T01:00:00+00:00',
                'attendees'     => $expectedExceptionAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 4. Check recurring events in 1st attendee user calendar. There should be no exception event.
        $attendeeRecurringEvent1 = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_2')
        );
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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent1->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent1->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent1->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 5. Check recurring events in 2nd attendee user calendar.
        //         There should be 1 attendee in exception event of owner user calendar.
        $attendeeRecurringEvent2 = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_3')
        );

        $attendeeExceptionEvent2 = $exceptionEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_3')
        );

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

        $expectedAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email'       => 'foo_user_2@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedExceptionAttendees = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResponse = [
            [
                'id'          => $attendeeRecurringEvent2->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent2->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringEvent2->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-03T01:00:00+00:00',
                'end'         => '2016-04-03T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'            => $attendeeExceptionEvent2->getId(),
                'title'         => 'Test Recurring Event',
                'description'   => 'Test Recurring Event Description',
                'allDay'        => false,
                'calendar'      => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'         => '2016-04-04T01:00:00+00:00',
                'end'           => '2016-04-04T02:00:00+00:00',
                'originalStart' => '2016-04-04T01:00:00+00:00',
                'attendees'     => $expectedExceptionAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }

    /**
     * Exception event with overridden attendees not added on calendar of new attendee added to recurring event.
     *
     * Steps:
     * 1. Create recurring event with two attendees "A" and "B".
     * 2. Create exception for the recurring event with attendee "C" without user and attendee "D" with user.
     * 3. Update recurring calendar event, add attendee "E" to all recurring events.
     * 4. Check exception event is not added on the calendar of attendee "E" user.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExceptionEventWithOverriddenAttendeesNotAddedOnCalendarOfNewAttendeeAddedToRecurringEvent()
    {
        // Step 1. Create recurring event with two attendees "A" and "B".
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03
        $eventData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-01T01:00:00+00:00',
            'end'              => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor'  => '#FF0000',
            'attendees'        => [
                [
                    'displayName' => 'External Attendee',
                    'email'       => 'ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                    'email'       => 'foo_user_1@example.com',
                    'status'      => Attendee::STATUS_ACCEPTED,
                    'type'        => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'       => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 3,
            ],
            'notifyAttendees'  => NotificationManager::ALL_NOTIFICATIONS_STRATEGY
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

        // Step 2. Create exception for the recurring event with attendee "C" without user and attendee "D" with user.
        $exceptionData = [
            'title'            => 'Test Recurring Event Changed',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-04-03T03:00:00+00:00',
            'end'              => '2016-04-03T04:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-04-03T01:00:00+00:00',
            'attendees'        => [
                [
                    'displayName' => 'Second External Attendee',
                    'email'       => 'second_ext@example.com',
                    'status'      => Attendee::STATUS_NONE,
                    'type'        => Attendee::TYPE_OPTIONAL,
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
                'content' => json_encode($exceptionData, JSON_THROW_ON_ERROR)
            ]
        );
        $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        // Step 3. Update recurring calendar event, add attendee "E" to all recurring events.
        $eventData['attendees'] = [
            [
                'displayName' => 'External Attendee',
                'email'       => 'ext@example.com',
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 4. Check exception event is not added on the calendar of attendee "E" user.
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

        $expectedAttendees = [
            [
                'displayName' => 'External Attendee',
                'email'       => 'ext@example.com',
                'status'      => Attendee::STATUS_NONE,
                'type'        => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email'       => 'foo_user_3@example.com',
                'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status'      => Attendee::STATUS_ACCEPTED,
                'type'        => Attendee::TYPE_REQUIRED,
            ],
        ];

        $recurringEvent = $this->reloadEntity($recurringEvent);
        $attendeeRecurringCalendarEvent = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_3')
        );
        $expectedResponse = [
            [
                'id'          => $attendeeRecurringCalendarEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-01T01:00:00+00:00',
                'end'         => '2016-04-01T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ],
            [
                'id'          => $attendeeRecurringCalendarEvent->getId(),
                'title'       => 'Test Recurring Event',
                'description' => 'Test Recurring Event Description',
                'allDay'      => false,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'       => '2016-04-02T01:00:00+00:00',
                'end'         => '2016-04-02T02:00:00+00:00',
                'attendees'   => $expectedAttendees,
            ]
        ];

        $this->assertResponseEquals($expectedResponse, $response, false);
    }
}
