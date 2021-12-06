<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers basic operations with attendees of simple calendar events:
 *
 * Use cases covered:
 * - Create regular calendar event with attendee related to user.
 * - Create regular calendar event with attendee not related to any user.
 */
class BasicAttendeeTest extends AbstractTestCase
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
     * Create regular calendar event with attendee related to user.
     *
     * Steps:
     * 1. Create new calendar event with user attendee.
     * 2. Get created event and verify all properties in the response..
     */
    public function testCreateRegularCalendarEventWithAttendeeRelatedToUser()
    {
        // Step 1. Create new calendar event with attendees related to user.
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
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email'       => 'foo_user_2@example.com',
                            ]
                        ],
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
                'attendees'                => [
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                        'email'       => 'foo_user_2@example.com',
                        'userId'      => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                        'status'      => Attendee::STATUS_NONE,
                        'type'        => Attendee::TYPE_REQUIRED,
                        'createdAt'   => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt'   => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
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
                'organizerUserId' => $newEvent->getOrganizerUser() ? $newEvent->getOrganizerUser()->getId() : null
            ],
            $response
        );
    }

    /**
     * Create regular calendar event with attendee not related to any user.
     *
     * Steps:
     * 1. Create regular calendar event with attendee not related to any user.
     * 2. Get created event and verify all properties in the response.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateRegularCalendarEventWithAttendeeNotRelatedToAnyUser()
    {
        // Step 1. Create regular calendar event with attendee not related to any user.
        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('system_user_1', 'system_user_1_api_key'),
                'content' => json_encode(
                    [
                        'title'     => 'Regular event',
                        'start'     => '2016-10-14T22:00:00+00:00',
                        'end'       => '2016-10-14T23:00:00+00:00',
                        'calendar'  => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
                        'attendees' => [
                            [
                                'displayName' => 'External Attendee',
                                'email'       => 'ext@example.com',
                                'status'      => Attendee::STATUS_TENTATIVE,
                                'type'        => Attendee::TYPE_ORGANIZER,
                            ]
                        ],
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
            [
                'id'                        => $response['id'],
                'uid'                       => $response['uid'],
                'invitationStatus'          => Attendee::STATUS_NONE,
                'editableInvitationStatus'  => false,
                'organizerDisplayName'      => 'Elley Towards',
                'organizerEmail'            => 'system_user_1@example.com',
                'organizerUserId'           => $response['organizerUserId']
            ],
            $response
        );

        // Step 2. Get created event and verify all properties in the response.
        $this->restRequest(
            [
                'method' => 'GET',
                'url'    => $this->getUrl('oro_api_get_calendarevent', ['id' => $newEvent->getId()]),
                'server' => $this->generateWsseAuthHeader('system_user_1', 'system_user_1_api_key')
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
                'calendar'                 => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
                'parentEventId'            => null,
                'title'                    => 'Regular event',
                'description'              => null,
                'start'                    => '2016-10-14T22:00:00+00:00',
                'end'                      => '2016-10-14T23:00:00+00:00',
                'allDay'                   => false,
                'attendees'                => [
                    [
                        'displayName' => 'External Attendee',
                        'email'       => 'ext@example.com',
                        'userId'      => null,
                        'status'      => Attendee::STATUS_TENTATIVE,
                        'type'        => Attendee::TYPE_ORGANIZER,
                        'createdAt'   => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt'   => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
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
                'organizerUserId'  => $newEvent->getOrganizerUser() ? $newEvent->getOrganizerUser()->getId() : null
            ],
            $response
        );
    }

    /**
     * Create regular calendar event with attendee related to user from other organization.
     *
     * Steps:
     * 1. Create regular calendar event with attendee not related to any user.
     * 2. Get created event and verify all properties in the response.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateRegularCalendarEventWithAttendeeRelatedToUserFromOtherOrganization()
    {
        // Step 1. Create regular calendar event with attendee not related to any user.
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
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:bar_user_1')->getFullName(),
                                'email'       => 'bar_user_1@example.com',
                                'status'      => null,
                                'type'        => Attendee::TYPE_REQUIRED,
                            ]
                        ],
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
                'attendees'                => [
                    [
                        'displayName' => $this->getReference('oro_calendar:user:bar_user_1')->getFullName(),
                        'email'       => 'bar_user_1@example.com',
                        'userId'      => null,
                        'status'      => Attendee::STATUS_NONE,
                        'type'        => Attendee::TYPE_REQUIRED,
                        'createdAt'   => $newEvent->getAttendeeByEmail('bar_user_1@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt'   => $newEvent->getAttendeeByEmail('bar_user_1@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
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
                'organizerUserId' => $newEvent->getOrganizerUser() ? $newEvent->getOrganizerUser()->getId() : null
            ],
            $response
        );
    }

    /**
     * Update regular calendar event with already existing attendees.
     *
     * Steps:
     * 1. Create new calendar event with attendees related to user.
     * 2. Update the calendar event with 2 new attendees.
     * 3. Get the event and verify the response contain only 2 new attendees.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRegularCalendarEventWithAlreadyExistingAttendees()
    {
        // Step 1. Create new calendar event with attendees related to user.
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
                        'attendees' => [
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email'       => 'foo_user_2@example.com',
                                'status'      => Attendee::STATUS_ACCEPTED,
                                'type'        => Attendee::TYPE_REQUIRED,
                            ]
                        ],
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

        // Step 2. Update the calendar event with 2 new attendees.
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'attendees' => [
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
                    JSON_THROW_ON_ERROR
                )
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
                'uid'                       => $response['uid'],
                'invitationStatus'          => Attendee::STATUS_NONE,
                'editableInvitationStatus'  => false,
                'organizerDisplayName'      => 'Billy Wilf',
                'organizerEmail'            => 'foo_user_1@example.com',
                'organizerUserId'           => $response['organizerUserId']
            ],
            $response
        );

        // Step 3. Get the event and verify the response contain only 2 new attendees.
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

        $newEvent = $this->reloadEntity($newEvent);

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
                'attendees'                => [
                    [
                        'displayName' => 'External Attendee',
                        'email'       => 'ext@example.com',
                        'userId'      => null,
                        'status'      => Attendee::STATUS_NONE,
                        'type'        => Attendee::TYPE_OPTIONAL,
                        'createdAt'   => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt'   => $newEvent->getAttendeeByEmail('ext@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ],
                    [
                        'displayName' => $this->getReference('oro_calendar:user:foo_user_3')->getFullName(),
                        'email'       => 'foo_user_3@example.com',
                        'userId'      => $this->getReference('oro_calendar:user:foo_user_3')->getId(),
                        'status'      => Attendee::STATUS_ACCEPTED,
                        'type'        => Attendee::TYPE_REQUIRED,
                        'createdAt'   => $newEvent->getAttendeeByEmail('foo_user_3@example.com')
                            ->getCreatedAt()->format(DATE_RFC3339),
                        'updatedAt'   => $newEvent->getAttendeeByEmail('foo_user_3@example.com')
                            ->getUpdatedAt()->format(DATE_RFC3339),
                    ]
                ],
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
                'organizerUserId' => $newEvent->getOrganizerUser() ? $newEvent->getOrganizerUser()->getId() : null
            ],
            $response
        );
    }

    /**
     * Update regular calendar event with empty list of attendees to remove existed attendees.
     *
     * Steps:
     * 1. Create new calendar event with attendees related to user.
     * 2. Update the calendar event with empty list of attendees.
     * 3. Get the event and verfy the response contain only 2 new attendees.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateRregularCalendarEventWithEmptyListOfAttendeesToremoveExistedAttendees()
    {
        // Step 1. Create new calendar event with attendees related to user.
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
                        'attendees' => [
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

        // Step 2. Update the calendar event with empty list of attendees.
        $this->restRequest(
            [
                'method'  => 'PUT',
                'url'     => $this->getUrl('oro_api_put_calendarevent', ['id' => $newEvent->getId()]),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'attendees' => [],
                    ],
                    JSON_THROW_ON_ERROR
                )
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
                'uid'                       => $response['uid'],
                'invitationStatus'          => Attendee::STATUS_NONE,
                'editableInvitationStatus'  => false,
                'organizerDisplayName'      => 'Billy Wilf',
                'organizerEmail'            => 'foo_user_1@example.com',
                'organizerUserId'           => $response['organizerUserId']
            ],
            $response
        );

        // Step 3. Get the event and verfy the response contain only 2 new attendees.
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

        $newEvent = $this->reloadEntity($newEvent);

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
                'removable'                => true,
                'backgroundColor'          => null,
                'invitationStatus'         => Attendee::STATUS_NONE,
                'recurringEventId'         => null,
                'originalStart'            => null,
                'isCancelled'              => false,
                'createdAt'                => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                'updatedAt'                => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                'editableInvitationStatus' => false,
                'isOrganizer'              => $newEvent->isOrganizer(),
                'organizerDisplayName'     => $newEvent->getOrganizerDisplayName(),
                'organizerEmail'           => $newEvent->getOrganizerEmail(),
                'organizerUserId'          =>
                    $newEvent->getOrganizerUser() ? $newEvent->getOrganizerUser()->getId() : null
            ],
            $response
        );
    }
}
