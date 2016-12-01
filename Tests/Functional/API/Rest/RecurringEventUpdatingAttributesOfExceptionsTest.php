<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

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
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 * - update event (oro_api_put_calendarevent)
 * - get events (oro_api_get_calendarevents)
 *
 * @dbIsolationPerTest
 */
class RecurringEventUpdatingAttributesOfExceptionsTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);  // force load fixtures
    }

    /**
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
        // Step 1. Create new recurring event
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
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 2. Create exception for the recurring event, exception represents changed event
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
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $changedEventException */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Get events via API and verify result is expected
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

        $response = $this->getRestResponseContent(
            [
                'statusCode' => 200,
                'contentType' => 'application/json'
            ]
        );

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

        $responseWithException = [
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
        $responseWithException[] = $exceptionData;

        $this->assertResponseEquals($responseWithException, $response, false);

        // Step 4. Change recurring event.
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
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 200,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            [
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_NONE,
                'isCurrentUserInvited' => false,
            ],
            $response
        );

        // Step 5. Get events via API and verify result is expected
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

        $response = $this->getRestResponseContent(
            [
                'statusCode' => 200,
                'contentType' => 'application/json'
            ]
        );

        $expectedResult = $responseWithException;

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
}
