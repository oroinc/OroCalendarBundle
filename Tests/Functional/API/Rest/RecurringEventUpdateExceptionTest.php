<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers recurring event exceptions clear logic.
 *
 * Operations covered:
 * - change recurrence without allowance to clear exceptions.
 * - change recurrence with allowance to clear exceptions.
 * - empty existing recurrence with allowance to clear exceptions.
 * - empty existing recurrence without allowance to clear exceptions.
 * - change other event data but not recurrence to not clear exceptions.
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 * - update event (oro_api_put_calendarevent)
 * - get events (oro_api_get_calendarevents)
 *
 * @dbIsolationPerTest
 */
class RecurringEventUpdateExceptionTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);  // force load fixtures
    }

    /**
     * Create recurring calendar event and exceptions.
     *
     * Change event recurrence and verify exceptions are cleared.
     * Verify exceptions are cleared when recurrence was changed/empty and clear allowed.
     * Verify exceptions are not cleared when recurrence was changed/empty and clear not allowed.
     * Verify exceptions are not cleared when recurrence was not changed.
     *
     * @dataProvider updateExceptionsDataProvider
     *
     * @param array $changedEventData
     * @param bool $expectExceptionsCleared
     * @param bool $expectRemoveRecurrence
     */
    public function testUpdateRecurringEventRecurrenceClearsExceptions(
        array $changedEventData,
        $expectExceptionsCleared,
        $expectRemoveRecurrence = false
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

        // Step 2. Create exception for the recurring event, exception represents cancelled event
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
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
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $cancelledEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 3. Create exception for the recurring event, exception represents changed event
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
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
                    ]
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $changedEventException = $this->getEntity(CalendarEvent::class, $response['id']);

        // Step 4. Get events via API and verify result is expected
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

        $responseWithExceptions = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-25T01:00:00+00:00',
                'end'              => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-05-08T01:00:00+00:00',
                'end'              => '2016-05-08T02:00:00+00:00',
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

        $responseWithClearedExceptions = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-25T01:00:00+00:00',
                'end'              => '2016-04-25T02:00:00+00:00',
            ],
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-05-08T01:00:00+00:00',
                'end'              => '2016-05-08T02:00:00+00:00',
            ],
            [
                'id'               => $recurringEvent->getId(),
                'isCancelled'      => false,
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-05-09T01:00:00+00:00',
                'end'              => '2016-05-09T02:00:00+00:00',
            ],
        ];

        $responseWithNoRecurringEvents = [
            [
                'id'               => $recurringEvent->getId(),
                'title'            => 'Test Recurring Event',
                'description'      => 'Test Recurring Event Description',
                'allDay'           => false,
                'calendar'         => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'            => '2016-04-25T01:00:00+00:00',
                'end'              => '2016-04-25T02:00:00+00:00',
            ]
        ];

        $this->assertResponseEquals($responseWithExceptions, $response, false);

        // Step 5. Change recurring event.
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
            ],
            $response
        );

        // Step 6. Get events via API and verify result is expected
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

        if ($expectRemoveRecurrence) {
            $expectedResponse = $responseWithNoRecurringEvents;
        } elseif ($expectExceptionsCleared) {
            $expectedResponse = $responseWithClearedExceptions;
        } else {
            $expectedResponse = $responseWithExceptions;
        }

        $this->assertResponseEquals($expectedResponse, $response, false);

        $this->getEntityManager()->clear();
        if ($expectExceptionsCleared) {
            $this->assertNull(
                $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
                'Failed asseting exception is removed when cleared.'
            );
            $this->assertNull(
                $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
                'Failed asseting exception is removed when cleared.'
            );
        } else {
            $this->assertNotNull(
                $this->getEntity(CalendarEvent::class, $cancelledEventException->getId()),
                'Failed asseting exception is not removed when not cleared.'
            );
            $this->assertNotNull(
                $this->getEntity(CalendarEvent::class, $changedEventException->getId()),
                'Failed asseting exception is not removed when not cleared.'
            );
        }
    }

    /**
     * @return array
     */
    public function updateExceptionsDataProvider()
    {
        return [
            'Exceptions cleared when recurrence updated and updateExceptions is true' => [
                'changedEventData' => [
                    'recurrence' => ['endTime' => null, 'occurrences' => 3],
                    'updateExceptions' => true,
                ],
                'expectExceptionsCleared' => true,
            ],
            'Exceptions not cleared when recurrence updated and updateExceptions is false' => [
                'changedEventData' => [
                    'recurrence' => ['endTime' => null, 'occurrences' => 3],
                ],
                'expectExceptionsCleared' => false
            ],
            'Exceptions not cleared when recurrence is not changed and updateExceptions is true' => [
                'changedEventData' => [
                    'start' => '2016-04-25T01:00:00+00:00',
                    'end' => '2016-04-25T02:00:00+00:00',
                    'updateExceptions' => true,
                ],
                'expectExceptionsCleared' => false
            ],
            'Exceptions cleared when recurrence is not changed and updateExceptions is true, end date is changed' => [
                'changedEventData' => [
                    'start' => '2016-05-25T01:00:00+00:00',
                    'end' => '2016-05-25T02:00:00+00:00',
                    'updateExceptions' => true,
                ],
                'expectExceptionsCleared' => true
            ],
            'Exceptions cleared when recurrence set empty and updateExceptions is true' => [
                'changedEventData' => [
                    'recurrence' => null,
                    'updateExceptions' => true,
                ],
                'expectExceptionsCleared' => true,
                'expectRemoveRecurrence' => true,
            ],
            'Exceptions not cleared when recurrence set empty and updateExceptions is false' => [
                'changedEventData' => [
                    'recurrence' => null
                ],
                'expectExceptionsCleared' => false,
                'expectRemoveRecurrence' => true,
            ],
        ];
    }
}
