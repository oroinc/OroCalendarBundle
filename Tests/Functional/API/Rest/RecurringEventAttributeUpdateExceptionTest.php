<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers recurring event updating attributes of exceptions after changes in all series.
 *
 * Operations covered:
 * - update any of next fields: title, description, backgroundColor, allDay
 * - attendee attribute is not updated in the exception
 * - new attendee is added to all series and in exception attendees are the same with all series
 * - attendee is changed in all series and in exception too(in exception attendees are the same with all series)
 * - attendee is removed from all series and in exception attendees are the same with all series
 * - adding guests to exception and then adding the same guests to the all series
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 * - update event (oro_api_put_calendarevent)
 * - get events (oro_api_get_calendarevents)
 *
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RecurringEventAttributeUpdateExceptionTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);  // force load fixtures
    }

    /**
     * The test covers next use case:
     * 1. Create recurring event with 2 attendees.
     * 2. Create exception for the recurring event with changed values of attributes.
     * 3. Change recurring event.
     * 4. Get events via API and verify result is expected.
     *
     * @dataProvider updateExceptionsDataProvider
     *
     * @param array $changedEventData
     * @param array $exceptionChangedData
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateAttributesOfExceptionsAfterAllSeriesChanges(
        array $changedEventData,
        array $exceptionChangedData
    ) {
        // Step 1. Create recurring event with 2 attendees.
        // Recurring event with occurrences: 2016-04-25, 2016-05-08, 2016-05-09, 2016-05-22
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-25T01:00:00+00:00',
            'end'         => '2016-04-25T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ]
            ],
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
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception for the recurring event with changed values of attributes.
        $exceptionData = [
            'title'            => 'Test Recurring Event',
            'description'      => 'Test Recurring Event Description',
            'allDay'           => false,
            'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'            => '2016-05-22T03:00:00+00:00',
            'end'              => '2016-05-22T05:00:00+00:00',
            'backgroundColor'  => '#FF0000',
            'recurringEventId' => $recurringEvent->getId(),
            'originalStart'    => '2016-05-22T01:00:00+00:00',
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ]
            ],
        ];
        $exceptionData = array_replace_recursive($exceptionData, $exceptionChangedData);
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Change recurring event.
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    array_replace_recursive(
                        $eventData,
                        $changedEventData
                    )
                )
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 4. Get events via API and verify result is expected.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'ext@example.com',
                'userId' => null,
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
                'createdAt' => $recurringEvent->getAttendeeByEmail('ext@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $recurringEvent->getAttendeeByEmail('ext@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
                'createdAt' => $recurringEvent->getAttendeeByEmail('foo_user_3@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $recurringEvent->getAttendeeByEmail('foo_user_3@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ]
        ];
        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-25T01:00:00+00:00',
                'end'              => '2016-04-25T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-05-08T01:00:00+00:00',
                'end'              => '2016-05-08T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-05-09T01:00:00+00:00',
                'end'              => '2016-05-09T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ]
        ];
        $exceptionData['id'] = $changedEventException->getId();
        $exceptionData['attendees'] = [
            [
                'displayName' => 'External Attendee',
                'email' => 'ext@example.com',
                'userId' => null,
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
                'createdAt' => $changedEventException->getAttendeeByEmail('ext@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $changedEventException->getAttendeeByEmail('ext@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
                'createdAt' => $changedEventException->getAttendeeByEmail('foo_user_3@example.com')
                    ->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $changedEventException->getAttendeeByEmail('foo_user_3@example.com')
                    ->getUpdatedAt()->format(DATE_RFC3339),
            ]
        ];
        $expectedResult[] = $exceptionData;
        foreach ($expectedResult as &$item) {
            $item = array_replace_recursive($item, $changedEventData);
            if (!empty($item['recurringEventId'])) {
                $item = array_replace_recursive($item, $exceptionChangedData);
            }
        }
        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * @return array
     */
    public function updateExceptionsDataProvider()
    {
        return [
            'All simple attributes should be changed, but title' => [
                'changedEventData' => [
                    'title' => 'New Test Recurring Event Title',
                    'description' => 'New Description',
                    'allDay'      => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'title' => 'Test Recurring Event Changed'
                ]
            ],
            'All simple attributes should be changed, but description' => [
                'changedEventData' => [
                    'title' => 'New Test Recurring Event Title',
                    'description' => 'New Description',
                    'allDay'      => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'description' => 'Test Recurring Event Description Changed',
                ]
            ],
            'All simple attributes should be changed, but allDay' => [
                'changedEventData' => [
                    'title' => 'New Test Recurring Event Title',
                    'description' => 'New Description',
                    'allDay'      => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'allDay'      => true,
                ]
            ],
            'All simple attributes should be changed, but backgroundColor' => [
                'changedEventData' => [
                    'title' => 'New Test Recurring Event Title',
                    'description' => 'New Description',
                    'allDay'      => true,
                    'backgroundColor' => '#0000FF'
                ],
                'exceptionChangedData' => [
                    'backgroundColor' => '#FF00FF',
                ]
            ],
        ];
    }

    /**
     * The test covers next use case:
     * 1. Create recurring event with 2 attendees.
     * 2. Add exception with updated title, description and time.
     * 3. Update recurring event one more attendee not related to user.
     * 4. Check the list of attendees is the same in each occurrence of including the exception.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testNoUserAttendeeAddedToExceptionWhenAddedToRecurringEvent()
    {
        // Step 1. Create recurring event with 2 attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
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
        $exceptionEventId = $response['id'];

        // Step 3. Update recurring event one more attendee not related to user.
        $eventData['attendees'] = [
            [
                'displayName' => 'External Attendee',
                'email' => 'ext@example.com',
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => 'Another External Attendee',
                'email' => 'another_ext@example.com',
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
            ]
        ];
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 4. Get events via API and verify result is expected, it is expected to have new attendee added
        // to all events in the series including exception event.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'another_ext@example.com',
                'userId' => null,
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => 'External Attendee',
                'email' => 'ext@example.com',
                'userId' => null,
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $exceptionEventId,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * The test covers next use case:
     * 1. Create recurring event with 2 attendees.
     * 2. Add exception with updated title, description and time.
     * 3. Update recurring event one more attendee.
     * 4. Check the list of attendees is the same in each occurrence of including the exception.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUserAttendeeAddedToExceptionWhenAddedToRecurringEvent()
    {
        // Step 1. Create recurring event with 2 attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
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
                ]
            ],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
        $exceptionEventId = $response['id'];

        // Step 3. Update recurring event one more attendee.
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
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
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

        // Step 4. Check the list of attendees is the same in each occurrence of including the exception.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'foo_user_1@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $exceptionEventId,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * The test covers next use case:
     * 1. Create new recurring event with 2 attendees.
     * 2. Add exception with updated title, description and time.
     * 3. Update recurring event, remove one of the attendees and add another attendee instead.
     * 4. Check the list of attendees is the same in each occurrence of including the exception.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAttendeeReplacedInExceptionWhenReplacedInRecurringEvent()
    {
        // Step 1. Create new recurring event with 2 attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
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
        $exceptionEventId = $response['id'];

        // Step 3. Update recurring event, remove one of the attendees and add another attendee instead.
        $eventData['attendees'] = [
            [
                'displayName' => 'Another External Attendee',
                'email' => 'aext@example.com',
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'status' => Attendee::STATUS_DECLINED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 4. Check the list of attendees is the same in each occurrence of including the exception.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'aext@example.com',
                'userId' => null,
                'status' => Attendee::STATUS_NONE,
                'type' => Attendee::TYPE_OPTIONAL,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status' => Attendee::STATUS_DECLINED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];
        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $exceptionEventId,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * The test covers next use case:
     * 1. Create recurring event with 2 attendees.
     * 2. Create exception for the recurring event with updated title, description and time.
     * 3. Remove attendee from the recurring event.
     * 4. Check the list of attendees is the same in each occurrence of including the exception.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAttendeeRemovedFromExceptionWhenRemovedFromRecurringEvent()
    {
        // Step 1. Create recurring event with 2 attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
                    'status' => Attendee::STATUS_ACCEPTED,
                    'type' => Attendee::TYPE_REQUIRED,
                ]
            ],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception for the recurring event with updated title, description and time.
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
            'attendees' => [
                [
                    'displayName' => 'External Attendee',
                    'email' => 'ext@example.com',
                    'status' => Attendee::STATUS_NONE,
                    'type' => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
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
        $exceptionEventId = $response['id'];

        // Step 3. Remove attendee from the recurring event.
        $eventData['attendees'] = [
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                'email' => 'foo_user_3@example.com',
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 4. Check the list of attendees is the same in each occurrence of including the exception.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'foo_user_3@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $exceptionEventId,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     *
     * The test covers next use case:
     * 1. Create recurring event without attendees.
     * 2. Create exception for the recurring event with updated title, description, time and with 2 attendees.
     * 3. Change recurring event with the same attendees as in exception.
     * 4. Check the attendees list is the same in each occurrence of recurring event including the exception.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateAddedUserAsAttendeeOfExceptionsAfterAllSeriesChanges()
    {
        // Step 1. Create recurring event without attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception for the recurring event with updated title, description, time and with 2 attendees.
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
        $exceptionEventId = $response['id'];

        // Step 3. Change recurring event with the same attendees as in exception.
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
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
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

        // Step 4. Check the attendees list is the same in each occurrence of recurring event including the exception.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'foo_user_1@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResult = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $exceptionEventId,
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * The test covers next use case:
     * 1. Create recurring calendar event without attendees.
     * 2. Create exception event with 2 attendees.
     * 3. Remove attendees from exception event.
     * 4. Add 2 attendees to all recurring events.
     * 5. Check attendees has the same list of calendar events as calendar event owner.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCalendarEventForAttendeeUsersAddedWhenExceptionHadAttendeesBeforeAndExistWithoutAttendees()
    {
        // Step 1. Create recurring calendar event without attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception event with 2 attendees.
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
        $exceptionEventId = $response['id'];

        // Step 3. Remove attendees from exception event.
        $exceptionData['attendees'] = [];
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $exceptionEventId]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_NONE,
                'editableInvitationStatus' => false,
            ],
            $response
        );

        // Step 4. Add 2 attendees to all recurring events.
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
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
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

        // Step 5. Check attendees has the same list of calendar events as calendar event owner.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'foo_user_1@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $recurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId());
        $attendeeCalendarEvent = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_2')
        );
        $attendeeExceptionCalendarEvent = $attendeeCalendarEvent->getRecurringEventExceptions()->first();
        $expectedResult = [
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeExceptionCalendarEvent->getId(),
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * The test covers next use case:
     * 1. Create recurring calendar event without attendees.
     * 2. Create exception event with custom title, time, description.
     * 3. Add 2 attendees to all recurring events.
     * 4. Check attendees has the same list of calendar events as calendar event owner.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCalendarEventForAttendeeUsersAddedWhenExceptionExistWithoutAttendees()
    {
        // Step 1. Create recurring event without attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
            'updateExceptions' => true,
            'backgroundColor' => '#FF0000',
            'attendees' => [],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);

        // Step 3. Add 2 attendees to all recurring events.
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
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
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

        // Step 4. Check attendees has the same list of calendar events as calendar event owner.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'foo_user_1@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $recurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId());
        $attendeeCalendarEvent = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_2')
        );
        $attendeeExceptionCalendarEvent = $attendeeCalendarEvent->getRecurringEventExceptions()->first();
        $expectedResult = [
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeExceptionCalendarEvent->getId(),
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }

    /**
     * The test covers next use case:
     * 1. Create recurring calendar event with 2 attendees.
     * 2. Create exception event with custom title, time, description and without attendees.
     * 3. Check attendees have no event in the calendar where they were removed.
     * 4. Check exception event for removed attendee has flag isCancelled=TRUE.
     * 5. Add 2 attendees back to the exception event.
     * 6. Check attendees has the same list of calendar events as calendar event owner.
     * 7. Check exception event for attendee which was added after it was removd has flag isCancelled=FALSE.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCalendarEventForAttendeeUsersAddedRemovedAndAddedOnSecondTime()
    {
        // Step 1. Create recurring calendar event with 2 attendees.
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
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
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception event with custom title, time, description and without attendees.
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
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
        $exceptionEventId = $response['id'];

        // Step 3. Check attendees have no event in the calendar where they were removed.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'foo_user_1@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $recurringEvent = $this->getEntity(CalendarEvent::class, $recurringEvent->getId());
        $attendeeCalendarEvent = $recurringEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_2')
        );

        $expectedResult = [
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            // The last event on 2016-04-04 should not be exposed
        ];

        $this->assertResponseEquals($expectedResult, $response, false);

        // Step 4. Check exception event for removed attendee has flag isCancelled=TRUE.
        /** @var CalendarEvent $attendeeExceptionCalendarEvent */
        $attendeeExceptionCalendarEvent = $attendeeCalendarEvent->getRecurringEventExceptions()->first();
        $this->assertTrue(
            $attendeeExceptionCalendarEvent->isCancelled(),
            'Failed assert exception event for removed attendee has flag isCancelled=TRUE.'
        );

        // Step 5. Add 2 attendees back to the exception event.
        $exceptionData['attendees'] = [
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
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $exceptionEventId]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($exceptionData)
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'notifiable' => true,
                'invitationStatus' => Attendee::STATUS_ACCEPTED,
                'editableInvitationStatus' => true,
            ],
            $response
        );

        // Step 6. Check attendees has the same list of calendar events as calendar event owner.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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
                'email' => 'foo_user_1@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
            [
                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                'email' => 'foo_user_2@example.com',
                'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                'status' => Attendee::STATUS_ACCEPTED,
                'type' => Attendee::TYPE_REQUIRED,
            ],
        ];

        $expectedResult = [
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-01T01:00:00+00:00',
                'end'              => '2016-04-01T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-02T01:00:00+00:00',
                'end'              => '2016-04-02T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeCalendarEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-03T01:00:00+00:00',
                'end'              => '2016-04-03T02:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
            [
                'id'               => $attendeeExceptionCalendarEvent->getId(),
                'title'            => 'Test Recurring Event Changed',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                'start'            => '2016-04-04T03:00:00+00:00',
                'end'              => '2016-04-04T04:00:00+00:00',
                'attendees'        => $expectedAttendees,
            ],
        ];

        // @todo Fix issue in BAP-12986.
        $this->assertResponseEquals($expectedResult, $response, false);

        // Step 7. Check exception event for attendee which was added after it was removd has flag isCancelled=FALSE.
        $attendeeExceptionCalendarEvent = $this->reloadEntity($attendeeExceptionCalendarEvent);
        $this->assertFalse(
            $attendeeExceptionCalendarEvent->isCancelled(),
            'Failed assert exception event for removed attendee has flag isCancelled=TRUE.'
        );
    }

    /**
     * The test covers next use case:
     * 1. Create recurring calendar event with 2 Attendees
     * 2. Create exception event with 3 Attendees
     * 3. Change all recurring event series, change title and description
     * 4. Check third Attendee Calendar event titles was updated
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGuestCalendarEventOfExceptionShouldBeUpdated()
    {
        // Step 1. Create recurring calendar event with 2 Attendees
        // Recurring event with occurrences: 2016-04-01, 2016-04-02, 2016-04-03, 2016-04-04
        $eventData = [
            'title'       => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'allDay'      => false,
            'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'       => '2016-04-01T01:00:00+00:00',
            'end'         => '2016-04-01T02:00:00+00:00',
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
                ]
            ],
            'recurrence'  => [
                'timeZone'       => 'UTC',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2016-04-01T01:00:00+00:00',
                'occurrences'    => 4,
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
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception event with 3 Attendees
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
                [
                    'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                    'email' => 'foo_user_3@example.com',
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
        $exceptionEventId = $response['id'];

        // Step 3. Change all recurring event series, change title and description
        $eventData['title'] = 'Updated Title';
        $eventData['description'] = 'Updated Description';
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $recurringEvent->getId()]),
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

        // Step 4. Check third Attendee Calendar event titles was updated
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
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

        $exceptionEvent = $this->getEntity(CalendarEvent::class, $exceptionEventId);
        $attendeeExceptionCalendarEvent = $exceptionEvent->getChildEventByCalendar(
            $this->getReference('oro_calendar:calendar:foo_user_3')
        );
        $expectedResult = [
            [
                'id'               => $attendeeExceptionCalendarEvent->getId(),
                'title'            => 'Updated Title',
                'description'      => 'Updated Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_3')->getId(),
                'start'            => '2016-04-04T04:00:00+00:00',
                'end'              => '2016-04-04T05:00:00+00:00',
                'originalStart'    => '2016-04-04T01:00:00+00:00',
                'attendees'        => [
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                        'email' => 'foo_user_1@example.com',
                        'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                        'status' => Attendee::STATUS_ACCEPTED,
                        'type' => Attendee::TYPE_REQUIRED,
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                        'email' => 'foo_user_3@example.com',
                        'status' => Attendee::STATUS_ACCEPTED,
                        'type' => Attendee::TYPE_REQUIRED,
                        'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                        'email' => 'foo_user_2@example.com',
                        'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                        'status' => Attendee::STATUS_ACCEPTED,
                        'type' => Attendee::TYPE_REQUIRED,
                    ],
                ],
            ],
        ];

        $this->assertResponseEquals($expectedResult, $response, false);
    }
}
