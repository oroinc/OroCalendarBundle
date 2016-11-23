<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

/**
 * The test covers basic CRUD operations with simple calendar event.
 *
 * Operations covered:
 * - create new event with minimal required data
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 * - get event (oro_api_get_calendarevent)
 *
 * @dbIsolation
 */
class BasicCrudTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Create regular calendar event.
     *
     * Create of simple event with minimal required data.
     */
    public function testCreateSimpleCalendarEventWithUserAttendee()
    {
        // Step 1. Create regular calendar event using minimal required data in the request.
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title' => 'Regular event',
                        'start' => '2016-10-14T22:00:00+00:00',
                        'end' => '2016-10-14T23:00:00+00:00',
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
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
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            [
                'id' => $response['id'],
                'notifiable' => false,
                'invitationStatus' => Attendee::STATUS_NONE,
            ],
            $response
        );

        // Step 2. Get created event and verify all properties in the response.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
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
                'id' => $newEvent->getId(),
                'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'parentEventId' => null,
                'title' => "Regular event",
                'description' => null,
                'start' => "2016-10-14T22:00:00+00:00",
                'end' => "2016-10-14T23:00:00+00:00",
                'allDay' => false,
                'use_hangout' => false,
                'attendees' => [],
                'editable' => true,
                'removable' => true,
                'notifiable' => false,
                'backgroundColor' => null,
                'invitationStatus' => Attendee::STATUS_NONE,
                'recurringEventId' => null,
                'originalStart' => null,
                'isCancelled' => false,
                'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
            ],
            $response
        );
    }
}
