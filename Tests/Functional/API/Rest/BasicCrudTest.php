<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Faker\Provider\Uuid;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers basic CRUD operations with simple calendar event.
 *
 * Use cases covered:
 * - Create regular calendar event with minimal required data.
 * - Create simple event with from url encoded content.
 * - Update recurrence data of recurring calendar event changes "updatedAt" field.
 * - Delete attendee of calendar event changes "updatedAt" field.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class BasicCrudTest extends AbstractTestCase
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
            'id'                        => $response['id'],
            'uid'                       => $response['uid'],
            'invitationStatus'          => Attendee::STATUS_NONE,
            'editableInvitationStatus'  => false,
            'organizerDisplayName'      => 'Billy Wilf',
            'organizerEmail'            => 'foo_user_1@example.com',
            'organizerUserId'           => $response['organizerUserId']
        ];
    }

    /**
     * Create regular calendar event with minimal required data.
     *
     * Steps:
     * 1. Create regular calendar event using minimal required data in the request.
     * 2. Get created event and verify all properties in the response.
     */
    public function testCreateRegularCalendarEventWithMinimalRequiredData()
    {
        // Step 1. Create regular calendar event using minimal required data in the request.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'    => 'Regular event',
                        'start'    => '2016-10-14T22:00:00+00:00',
                        'end'      => '2016-10-14T23:00:00+00:00',
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'allDay'   => false
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
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 2. Get created event and verify all properties in the response.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $this->assertResponseEquals(
            [
                'id'                       => $newEvent->getId(),
                'uid'                      => $newEvent->getUid(),
                'calendar'                 => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'parentEventId'            => null,
                'title'                    => 'Regular event',
                'description'              => null,
                'start'                    => '2016-10-14T22:00:00+00:00',
                'end'                      => '2016-10-14T23:00:00+00:00',
                'allDay'                   => false,
                'attendees'                => [],
                'editable'                 => true,
                'editableInvitationStatus' => false,
                'removable'                => true,
                'backgroundColor'          => null,
                'invitationStatus'         => Attendee::STATUS_NONE,
                'recurringEventId'         => null,
                'originalStart'            => null,
                'isCancelled'              => false,
                'createdAt'                => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt'                => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                'isOrganizer'              => $newEvent->isOrganizer(),
                'organizerDisplayName'     => $newEvent->getOrganizerDisplayName(),
                'organizerEmail'           => $newEvent->getOrganizerEmail(),
                'organizerUserId'          => $newEvent->getOrganizerUser() ?
                    $newEvent->getOrganizerUser()->getId() : null
            ],
            $response
        );
    }

    /**
     * Create simple event with from url encoded content.
     *
     * Steps:
     * 1. Create regular calendar event using minimal required data in the request.
     * 2. Get created event and verify all properties in the response.
     */
    public function testCreateSimpleCalendarEventWithFormUrlEncodedContent()
    {
        $calendarId = $this->getReference('oro_calendar:calendar:foo_user_1')->getId();
        $uid = Uuid::uuid();
        // @codingStandardsIgnoreStart
        $content = <<<CONTENT
title=Regular%20event&uid=$uid&description=&start=2016-10-14T22%3A00%3A00.000Z&end=2016-10-14T23%3A00%3A00.000Z&allDay=false&attendees=&recurrence=&calendar=$calendarId
CONTENT;
        // @codingStandardsIgnoreEnd
        parse_str($content, $parameters);

        // Step 1. Create regular calendar event using minimal required data in the request.
        $this->restRequest(
            [
                'method'     => 'POST',
                'url'        => $this->getUrl('oro_api_post_calendarevent'),
                'server'     => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'parameters' => $parameters,
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 201,
                'contentType' => 'application/json'
            ]
        );
        /** @var CalendarEvent $newEvent */
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        // Step 2. Get created event and verify all properties in the response.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $this->assertResponseEquals(
            [
                'id'                       => $newEvent->getId(),
                'uid'                      => $newEvent->getUid(),
                'calendar'                 => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                'parentEventId'            => null,
                'title'                    => 'Regular event',
                'description'              => null,
                'start'                    => '2016-10-14T22:00:00+00:00',
                'end'                      => '2016-10-14T23:00:00+00:00',
                'allDay'                   => false,
                'attendees'                => [],
                'editable'                 => true,
                'editableInvitationStatus' => false,
                'removable'                => true,
                'backgroundColor'          => null,
                'invitationStatus'         => Attendee::STATUS_NONE,
                'recurringEventId'         => null,
                'originalStart'            => null,
                'isCancelled'              => false,
                'createdAt'                => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt'                => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                'isOrganizer'              => $newEvent->isOrganizer(),
                'organizerDisplayName'     => $newEvent->getOrganizerDisplayName(),
                'organizerEmail'           => $newEvent->getOrganizerEmail(),
                'organizerUserId'          => $newEvent->getOrganizerUser() ?
                    $newEvent->getOrganizerUser()->getId() : null
            ],
            $response
        );
    }

    /**
     * Update recurrence data of recurring calendar event changes "updatedAt" field.
     *
     * Steps:
     * 1. Create recurring event and save value of "updatedAt" field.
     * 2. Wait for 1 second.
     * 3. Update event and change only attribute in recurrence data.
     * 4. Get event and check the "updatedAt" value has been modified.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRecurrenceDataOfRecurringCalendarEventChangesUpdatedAtField()
    {
        // Step 1. Create recurring event and save value of "updatedAt" field.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'      => 'Recurring event',
                        'start'      => '2016-10-14T22:00:00+00:00',
                        'end'        => '2016-10-14T23:00:00+00:00',
                        'calendar'   => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'allDay'     => false,
                        'recurrence' => [
                            'timeZone'       => 'UTC',
                            'recurrenceType' => Recurrence::TYPE_DAILY,
                            'interval'       => 1,
                            'startTime'      => '2016-10-14T22:00:00+00:00',
                            'occurrences'    => 4,
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
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        $originalUpdatedAt = $newEvent->getUpdatedAt();
        $this->assertInstanceOf('DateTime', $originalUpdatedAt, 'Failed asserting "updatedAt" field was set.');

        // Step 2. Wait for 1 second.
        sleep(1);

        // Step 3. Update event and change only attribute in recurrence data.
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'recurrence' => [
                            'timeZone'       => 'UTC',
                            'recurrenceType' => Recurrence::TYPE_DAILY,
                            'interval'       => 2,
                            'startTime'      => '2016-10-14T22:00:00+00:00',
                            'occurrences'    => 4,
                        ]
                    ],
                    JSON_THROW_ON_ERROR
                )
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'uid'                       => $response['uid'],
                'invitationStatus'          => Attendee::STATUS_NONE,
                'editableInvitationStatus'  => false,
                'organizerDisplayName'      => 'Billy Wilf',
                'organizerEmail'            => 'foo_user_1@example.com',
                'organizerUserId'           => $response['organizerUserId']
            ],
            $response
        );

        // Step 4. Get event and check the "updatedAt" value has been modified.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $newUpdatedAt = new \DateTime($response['updatedAt'], new \DateTimeZone('UTC'));

        $diffInSeconds = $newUpdatedAt->getTimestamp() - $originalUpdatedAt->getTimestamp();

        $this->assertGreaterThanOrEqual(1, $diffInSeconds, 'Failed assertic "updatedAt" was updated.');
    }

    /**
     * Delete attendee of calendar event changes "updatedAt" field.
     *
     * Steps:
     * 1. Create regular event with 2 attendees and save value of "updatedAt" field.
     * 2. Wait for 1 second.
     * 3. Update event and delete 1 attendee.
     * 4. Get event and check the "updatedAt" value has been modified.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testDeleteAttendeeOfCalendarEventChangesUpdatedAtField()
    {
        // Step 1. Create regular event with 2 attendees and save value of "updatedAt" field.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'     => 'Regular event',
                        'start'     => '2016-10-14T22:00:00+00:00',
                        'end'       => '2016-10-14T23:00:00+00:00',
                        'calendar'  => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'allDay'    => false,
                        'attendees' => [
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
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $this->assertResponseEquals(
            $this->getResponseArray($response),
            $response
        );

        $originalUpdatedAt = $newEvent->getUpdatedAt();
        $this->assertInstanceOf('DateTime', $originalUpdatedAt, 'Failed asserting "updatedAt" field was set.');

        // Step 2. Wait for 1 second.
        sleep(1);

        // Step 3. Update event and delete 1 attendee.
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email'       => 'foo_user_2@example.com',
                                'status'      => Attendee::STATUS_ACCEPTED,
                                'type'        => Attendee::TYPE_REQUIRED,
                            ],
                        ]
                    ],
                    JSON_THROW_ON_ERROR
                )
            ]
        );
        $response = $this->getRestResponseContent(['statusCode' => 200, 'contentType' => 'application/json']);
        $this->assertResponseEquals(
            [
                'uid'                       => $response['uid'],
                'invitationStatus'          => Attendee::STATUS_NONE,
                'editableInvitationStatus'  => false,
                'organizerDisplayName'      => 'Billy Wilf',
                'organizerEmail'            => 'foo_user_1@example.com',
                'organizerUserId'           => $response['organizerUserId']
            ],
            $response
        );

        // Step 4. Get event and check the "updatedAt" value has been modified.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key')
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 200,
                'contentType' => 'application/json'
            ]
        );

        $newUpdatedAt = new \DateTime($response['updatedAt'], new \DateTimeZone('UTC'));

        $diffInSeconds = $newUpdatedAt->getTimestamp() - $originalUpdatedAt->getTimestamp();

        $this->assertGreaterThanOrEqual(1, $diffInSeconds, 'Failed assertic "updatedAt" was updated.');
    }

    public function testCheckIfCalendarIdIsValidated()
    {
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title' => 'Event withouth calendar set',
                        'start' => '2016-10-14T22:00:00+00:00',
                        'end' => '2016-10-14T23:00:00+00:00',
                        'allDay' => false,
                    ],
                    JSON_THROW_ON_ERROR
                )
            ]
        );

        $this->getRestResponseContent(['statusCode' => 201, 'contentType' => 'application/json']);
    }
}
