<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Functional\HangoutsCallDependentTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

/**
 * The test covers basic operations with attendees of simple calendar events:
 *
 * Types of attendees covered:
 * - attendee related to user by email
 * - attendee related to user from different organization by email
 * - attendee not related to user
 *
 * Operations covered:
 * - add attendee while creating new simple event
 * - add attendee while updating existing simple event
 * - remove attendee while updating existing simple event
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 * - update event (oro_api_put_calendarevent)
 * - get event (oro_api_get_calendarevent)
 *
 * @dbIsolation
 */
class BasicAttendeeTest extends HangoutsCallDependentTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Create regular calendar event with attendee related to user.
     *
     * Create regular calendar event with attendee related to user existing in the same organization.
     * It is expected attendee has id of related user.
     * It is expected attendee has "required" type by default.
     * It is expected attendee has "status" none by default.
     */
    public function testCreateSimpleCalendarEventWithUserAttendee()
    {
        // Step 1. Create new calendar event with attendees related to user.
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
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email' => 'foo_user_2@example.com',
                            ]
                        ],
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
                'notifiable' => true,
                'invitationStatus' => Attendee::STATUS_NONE,
                'isCurrentUserInvited' => false,
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
                'attendees' => [
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                        'email' => 'foo_user_2@example.com',
                        'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                        'status' => Attendee::STATUS_NONE,
                        'type' => Attendee::TYPE_REQUIRED,
                        'createdAt' => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
                'editable' => true,
                'removable' => true,
                'notifiable' => true,
                'backgroundColor' => null,
                'invitationStatus' => Attendee::STATUS_NONE,
                'recurringEventId' => null,
                'originalStart' => null,
                'isCancelled' => false,
                'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                'isCurrentUserInvited' => false,
                'calendarOwnerId' => $this->getReference('oro_calendar:user:foo_user_1')->getId()
            ],
            $response
        );
    }

    /**
     * Create regular calendar event with attendee not related to any user.
     *
     * Create regular calendar event with attendee not related to any user.
     * It is expected to have the attendee exist without related user.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateSimpleCalendarEventWithNonUserAttendee()
    {
        // Step 1. Create regular calendar event with attendee not related to any user.
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('system_user_1', 'system_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title' => 'Regular event',
                        'start' => '2016-10-14T22:00:00+00:00',
                        'end' => '2016-10-14T23:00:00+00:00',
                        'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
                        'attendees' => [
                            [
                                'displayName' => 'External Attendee',
                                'email' => 'ext@example.com',
                                'status' => Attendee::STATUS_TENTATIVE,
                                'type' => Attendee::TYPE_ORGANIZER,
                            ]
                        ],
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
                'notifiable' => true,
                'invitationStatus' => Attendee::STATUS_NONE,
                'isCurrentUserInvited' => false,
            ],
            $response
        );

        // Step 2. Get created event and verify all properties in the response.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('system_user_1', 'system_user_1_api_key')
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
                'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
                'parentEventId' => null,
                'title' => "Regular event",
                'description' => null,
                'start' => "2016-10-14T22:00:00+00:00",
                'end' => "2016-10-14T23:00:00+00:00",
                'allDay' => false,
                'attendees' => [
                    [
                        'displayName' => 'External Attendee',
                        'email' => 'ext@example.com',
                        'userId' => null,
                        'status' => Attendee::STATUS_TENTATIVE,
                        'type' => Attendee::TYPE_ORGANIZER,
                        'createdAt' => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt' => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
                'editable' => true,
                'removable' => true,
                'notifiable' => true,
                'backgroundColor' => null,
                'invitationStatus' => Attendee::STATUS_NONE,
                'recurringEventId' => null,
                'originalStart' => null,
                'isCancelled' => false,
                'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                'isCurrentUserInvited' => false,
                'calendarOwnerId' => $this->getReference('oro_calendar:user:system_user_1')->getId(),
            ],
            $response
        );
    }

    /**
     * Create regular calendar event with attendee related to user from other organization.
     *
     * Create regular calendar event with attendee related to user existing in different organization.
     * It is expected to not create a relation between the attendee and user from other organization.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateSimpleCalendarEventWithUserAttendeeFromOtherOrganization()
    {
        // Step 1. Create regular calendar event with attendee not related to any user.
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
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:bar_user_1')->getFullName(),
                                'email' => 'bar_user_1@example.com',
                                'status' => null,
                                'type' => Attendee::TYPE_REQUIRED,
                            ]
                        ],
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
                'notifiable' => true,
                'invitationStatus' => Attendee::STATUS_NONE,
                'isCurrentUserInvited' => false
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
                'attendees' => [
                    [
                        'displayName' => $this->getReference('oro_calendar:user:bar_user_1')->getFullName(),
                        'email' => 'bar_user_1@example.com',
                        'userId' => null,
                        'status' => Attendee::STATUS_NONE,
                        'type' => Attendee::TYPE_REQUIRED,
                        'createdAt' => $newEvent->getAttendeeByEmail('bar_user_1@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt' => $newEvent->getAttendeeByEmail('bar_user_1@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
                'editable' => true,
                'removable' => true,
                'notifiable' => true,
                'backgroundColor' => null,
                'invitationStatus' => Attendee::STATUS_NONE,
                'recurringEventId' => null,
                'originalStart' => null,
                'isCancelled' => false,
                'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                'isCurrentUserInvited' => false,
                'calendarOwnerId' => $this->getReference('oro_calendar:user:foo_user_1')->getId()
            ],
            $response
        );
    }

    /**
     * Update regular calendar event with already existing attendees.
     *
     * Create regular calendar event with 1 attendee.
     * Then update the event with 2 new attendees.
     * It is expected to have only 2 new attendees in the event and to not have previous attendee.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateSimpleCalendarEventWithExistingAttendees()
    {
        // Step 1. Create new calendar event with attendees related to user.
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
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email' => 'foo_user_2@example.com',
                                'status' => Attendee::STATUS_ACCEPTED,
                                'type' => Attendee::TYPE_REQUIRED,
                            ]
                        ],
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
                'notifiable' => true,
                'invitationStatus' => Attendee::STATUS_NONE,
                'isCurrentUserInvited' => false
            ],
            $response
        );

        // Step 2. Update the calendar event with 2 new attendees.
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
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
                    ]
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
                'notifiable' => true,
                'invitationStatus' => Attendee::STATUS_NONE,
                'isCurrentUserInvited' => false
            ],
            $response
        );

        // Step 3. Get the event and verify the response contain only 2 new attendees.
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

        $newEvent = $this->reloadEntity($newEvent);

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
                'attendees' => [
                    [
                        'displayName' => 'External Attendee',
                        'email' => 'ext@example.com',
                        'userId' => null,
                        'status' => Attendee::STATUS_NONE,
                        'type' => Attendee::TYPE_OPTIONAL,
                        'createdAt' => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt' => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                        'email' => 'foo_user_3@example.com',
                        'userId' => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                        'status' => Attendee::STATUS_ACCEPTED,
                        'type' => Attendee::TYPE_REQUIRED,
                        'createdAt' => $newEvent->getAttendeeByEmail('foo_user_3@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_3@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
                'editable' => true,
                'removable' => true,
                'notifiable' => true,
                'backgroundColor' => null,
                'invitationStatus' => Attendee::STATUS_NONE,
                'recurringEventId' => null,
                'originalStart' => null,
                'isCancelled' => false,
                'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                'isCurrentUserInvited' => false,
                'calendarOwnerId' => $this->getReference('oro_calendar:user:foo_user_1')->getId()
            ],
            $response
        );
    }

    /**
     * Update regular calendar event with empty list of attendees to remove existed attendees.
     *
     * Create regular calendar event with 2 attendees.
     * Then update the event with empty attendees.
     * It is expected to have no attendees in the event as a result.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateSimpleCalendarEventWithEmptyAttendees()
    {
        // Step 1. Create new calendar event with attendees related to user.
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
                'notifiable' => true,
                'invitationStatus' => Attendee::STATUS_NONE,
                'isCurrentUserInvited' => false
            ],
            $response
        );

        // Step 2. Update the calendar event with empty list of attendees.
        $this->restRequest(
            [
                'method' => 'PUT',
                'url' => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'attendees' => [],
                    ]
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
                'isCurrentUserInvited' => false
            ],
            $response
        );

        // Step 3. Get the event and verfy the response contain only 2 new attendees.
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

        $newEvent = $this->reloadEntity($newEvent);

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
                'isCurrentUserInvited' => false,
                'calendarOwnerId' => $this->getReference('oro_calendar:user:foo_user_1')->getId()
            ],
            $response
        );
    }
}
