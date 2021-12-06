<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers operations with daily recurring events.
 *
 * Use cases covered:
 * - Expanding of recurring event with recurrence pattern "Daily, every day" and with correct
 *   start/end dates for days when DST starts/ends.
 *
 * @dbIsolationPerTest
 */
class DailyRecurringEventTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Expanding of recurring event with recurrence pattern "Daily, every day" and with correct
     * start/end dates for days when DST starts/ends.
     *
     * Steps:
     * 1. Create new calendar event with pattern "Daily, every day, Start Sat 03/11/2017 No end date".
     * 2. Get expanded events and verify all properties in response.
     *    The significant part of response verification is correct "end" property according to DST starts.
     * 3. Get expanded events and verify all properties in response.
     *    The significant part of response verification is correct "end" property according to DST ends.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExpandingOfEventWithChangedDST()
    {
        // Step 1. Create new calendar event with pattern "Daily, every day, Start Sat 03/11/2017 No end date".
        $eventData = [
            'title'           => 'Test Daily Recurring Event (DST)',
            'description'     => 'Test Daily Recurring Event Description (DST)',
            'allDay'          => true,
            'calendar'        => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
            'start'           => '2017-03-11T08:00:00+00:00',
            'end'             => '2017-03-12T08:00:00+00:00',
            'recurrence'      => [
                'timeZone'       => 'America/Los_Angeles',
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval'       => 1,
                'startTime'      => '2017-03-11T08:00:00+00:00',
                'occurrences'    => null,
                'endTime'        => null,
            ],
            'attendees'       => [],
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

        // Step 2. Get expanded events and verify all properties in response.
        //         The significant part of response verification is correct "end" property according to DST starts.
        $response = $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
        /** @var CalendarEvent $recurringEvent */
        $recurringEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'       => '2017-03-10T00:00:00+00:00',
                        'end'         => '2017-03-15T00:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $expectedResponse = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Daily Recurring Event (DST)',
                'description' => 'Test Daily Recurring Event Description (DST)',
                'allDay'      => true,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2017-03-11T08:00:00+00:00',
                'end'         => '2017-03-12T08:00:00+00:00',
                'attendees'   => [],
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Daily Recurring Event (DST)',
                'description' => 'Test Daily Recurring Event Description (DST)',
                'allDay'      => true,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2017-03-12T08:00:00+00:00',
                'end'         => '2017-03-13T07:00:00+00:00', //DST starts here
                'attendees'   => [],
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Daily Recurring Event (DST)',
                'description' => 'Test Daily Recurring Event Description (DST)',
                'allDay'      => true,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2017-03-13T07:00:00+00:00',
                'end'         => '2017-03-14T07:00:00+00:00',
                'attendees'   => [],
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Daily Recurring Event (DST)',
                'description' => 'Test Daily Recurring Event Description (DST)',
                'allDay'      => true,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2017-03-14T07:00:00+00:00',
                'end'         => '2017-03-15T07:00:00+00:00',
                'attendees'   => [],
            ],
        ];
        $this->assertResponseEquals($expectedResponse, $response, false);

        // Step 3. Get expanded events and verify all properties in response.
        //         The significant part of response verification is correct "end" property according to DST ends.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start'       => '2017-11-04T00:00:00+00:00',
                        'end'         => '2017-11-07T00:00:00+00:00',
                        'subordinate' => true,
                    ]
                ),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $expectedResponse = [
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Daily Recurring Event (DST)',
                'description' => 'Test Daily Recurring Event Description (DST)',
                'allDay'      => true,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2017-11-04T07:00:00+00:00',
                'end'         => '2017-11-05T07:00:00+00:00',
                'attendees'   => [],
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Daily Recurring Event (DST)',
                'description' => 'Test Daily Recurring Event Description (DST)',
                'allDay'      => true,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2017-11-05T07:00:00+00:00',
                'end'         => '2017-11-06T08:00:00+00:00',
                'attendees'   => [],
            ],
            [
                'id'          => $recurringEvent->getId(),
                'title'       => 'Test Daily Recurring Event (DST)',
                'description' => 'Test Daily Recurring Event Description (DST)',
                'allDay'      => true,
                'calendar'    => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'start'       => '2017-11-06T08:00:00+00:00',
                'end'         => '2017-11-07T08:00:00+00:00',
                'attendees'   => [],
            ],
        ];
        $this->assertResponseEquals($expectedResponse, $response, false);
    }
}
