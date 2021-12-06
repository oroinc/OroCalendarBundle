<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers changes of invitation status of simple calendar event.
 *
 * Operations covered:
 * - create calendar connection
 * - create new simple calendar event with main attendee and attendee related to another user
 * - change invitation status of main event and child event
 * - get main and child events filtered by period of time and verify invitation status is exposed correctly after change
 *
 * Resources used:
 * - create calendar connection (oro_api_post_calendar_connection)
 * - create event (oro_api_post_calendarevent)
 * - get list event with start end filters (oro_api_post_calendarevents)
 * - set invitation status of the event to "accepted" (oro_calendar_event_accepted)
 * - set invitation status of the event to "declined" (oro_calendar_event_declined)
 * - set invitation status of the event to "tentative" (oro_calendar_event_tentative)
 */
class ChangeCalendarEventInvitationStatusTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Check invitation status of the event is updated using AJAX controller in the UI.
     *
     * Create regular calendar event with 2 attendees and update invitation status of the events.
     * It is expected the invitation status of main event and the child event can be updated.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCalendarEventInvitationStatusIsUpdatedForMainEventAndChildEvent()
    {
        // Step 1. Setup calendar connection to include calendar of attendee user.
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendar_connection'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode(
                    [
                        'targetCalendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                        'calendarAlias' => 'user',
                        'visible' => true
                    ],
                    JSON_THROW_ON_ERROR
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(['id' => $response['id']], $response);

        // Step 2. Create new calendar event with attendee related to user with different calendar.
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
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                                'email' => 'foo_user_1@example.com',
                                'status' => Attendee::STATUS_NONE,
                            ],
                            [
                                'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                                'email' => 'foo_user_2@example.com',
                                'status' => Attendee::STATUS_NONE,
                            ]
                        ]
                    ],
                    JSON_THROW_ON_ERROR
                )
            ]
        );
        $response = $this->getRestResponseContent(
            [
                'statusCode' => 201,
                'contentType' => 'application/json'
            ]
        );
        $this->assertResponseEquals(
            [
                'id'                        => $response['id'],
                'uid'                       => $response['uid'],
                'invitationStatus'          => Attendee::STATUS_NONE,
                'editableInvitationStatus'  => true,
                'organizerDisplayName'      => 'Billy Wilf',
                'organizerEmail'            => 'foo_user_1@example.com',
                'organizerUserId'           => $response['organizerUserId']
            ],
            $response
        );

        /** @var CalendarEvent $newEvent */
        $newEvent = $this->getEntity(CalendarEvent::class, $response['id']);
        $newChildEvent = $newEvent->getChildEventByCalendar($this->getReference('oro_calendar:calendar:foo_user_2'));

        // Step 3. Get the events and verify the invitation status is set to "none" in both events.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start' => '2016-10-01T00:00:00+00:00',
                        'end' => '2016-10-31T00:00:00+00:00',
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

        $this->assertResponseEquals(
            [
                [
                    'id' => $newEvent->getId(),
                    'uid' => $newEvent->getUid(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => null,
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_NONE,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'editableInvitationStatus' => true,
                    'removable' => true,
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => true,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newEvent)
                ],
                [
                    'id' => $newChildEvent->getId(),
                    'uid' => $newChildEvent->getUid(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => $newEvent->getId(),
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_NONE,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'editable' => false,
                    'editableInvitationStatus' => false,
                    'removable' => false,
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newChildEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newChildEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => false,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newChildEvent)
                ],
            ],
            $response
        );

        // Step 4. Update invitation status of the main event to "accepted".
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_calendar_event_accepted', ['id' => $newEvent->getId()]),
                'server' => array_merge(
                    $this->generateBasicAuthHeader(
                        'foo_user_1',
                        'password',
                        $this->getReference('oro_calendar:user:foo_user_1')->getOrganization()->getId()
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
                'successful' => true
            ],
            $response
        );

        // Step 5. Get the events and verify the invitation status is set to "accepted" in the main event
        // and "none" in the child event.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start' => '2016-10-01T00:00:00+00:00',
                        'end' => '2016-10-31T00:00:00+00:00',
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

        $newEvent = $this->reloadEntity($newEvent);

        $this->assertResponseEquals(
            [
                [
                    'id' => $newEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => null,
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_ACCEPTED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_ACCEPTED,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => true,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newEvent)
                ],
                [
                    'id' => $newChildEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => $newEvent->getId(),
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_ACCEPTED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'editable' => false,
                    'removable' => false,
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newChildEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newChildEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => false,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newChildEvent)
                ],
            ],
            $response,
            false
        );

        // Step 6. Update invitation status of the main event to "tentative".
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_calendar_event_tentative', ['id' => $newEvent->getId()]),
                'server' => array_merge(
                    $this->generateBasicAuthHeader('foo_user_1', 'password')
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
                'successful' => true
            ],
            $response
        );

        // Step 7. Get the events and verify the invitation status is set to "tentative" in the main event
        // and "none" in the child event.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start' => '2016-10-01T00:00:00+00:00',
                        'end' => '2016-10-31T00:00:00+00:00',
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

        $newEvent = $this->reloadEntity($newEvent);

        $this->assertResponseEquals(
            [
                [
                    'id' => $newEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => null,
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_TENTATIVE,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_TENTATIVE,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => true,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newEvent)
                ],
                [
                    'id' => $newChildEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => $newEvent->getId(),
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_TENTATIVE,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'editable' => false,
                    'removable' => false,
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newChildEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newChildEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => false,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newChildEvent)
                ],
            ],
            $response,
            false
        );

        // Step 8. Update invitation status of the main event to "declined".
        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_calendar_event_declined', ['id' => $newEvent->getId()]),
                'server' => array_merge(
                    $this->generateBasicAuthHeader('foo_user_1', 'password')
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
                'successful' => true
            ],
            $response
        );

        // Step 9. Get the events and verify the invitation status is set to "declined" in the main event
        // and "none" in the child event.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start' => '2016-10-01T00:00:00+00:00',
                        'end' => '2016-10-31T00:00:00+00:00',
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

        $newEvent = $this->reloadEntity($newEvent);

        $this->assertResponseEquals(
            [
                [
                    'id' => $newEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => null,
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_DECLINED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_DECLINED,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => true,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newEvent)
                ],
                [
                    'id' => $newChildEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => $newEvent->getId(),
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_DECLINED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
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
                    'editable' => false,
                    'removable' => false,
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_NONE,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newChildEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newChildEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => false,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newChildEvent)
                ],
            ],
            $response,
            false
        );

        // Step 10. Update invitation status of the child event to "accepted".

        // Clear session to to login with foo_user_2
        $this->getSession()->clear();

        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_calendar_event_accepted', ['id' => $newChildEvent->getId()]),
                'server' => array_merge(
                    $this->generateBasicAuthHeader(
                        'foo_user_2',
                        'password',
                        $this->getReference('oro_calendar:user:foo_user_2')->getOrganization()->getId()
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
                'successful' => true
            ],
            $response
        );

        // Clear session after login with foo_user_2
        $this->getSession()->clear();

        // Step 11. Get the events and verify the invitation status is set to "declined" in the main event
        // and "accepted" in the child event.
        $this->restRequest(
            [
                'method' => 'GET',
                'url' => $this->getUrl(
                    'oro_api_get_calendarevents',
                    [
                        'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                        'start' => '2016-10-01T00:00:00+00:00',
                        'end' => '2016-10-31T00:00:00+00:00',
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

        $newEvent = $this->reloadEntity($newEvent);
        $newChildEvent = $this->reloadEntity($newChildEvent);

        $this->assertResponseEquals(
            [
                [
                    'id' => $newEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_1')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => null,
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_DECLINED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'email' => 'foo_user_2@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                            'status' => Attendee::STATUS_ACCEPTED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ]
                    ],
                    'editable' => true,
                    'removable' => true,
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_DECLINED,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => true,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newEvent)
                ],
                [
                    'id' => $newChildEvent->getId(),
                    'calendar' => $this->getReference('oro_calendar:calendar:foo_user_2')->getId(),
                    'calendarAlias' => 'user',
                    'parentEventId' => $newEvent->getId(),
                    'title' => 'Regular event',
                    'description' => null,
                    'start' => '2016-10-14T22:00:00+00:00',
                    'end' => '2016-10-14T23:00:00+00:00',
                    'allDay' => false,
                    'attendees' => [
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_1')->getFullName(),
                            'email' => 'foo_user_1@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_1')->getId(),
                            'status' => Attendee::STATUS_DECLINED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_1@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ],
                        [
                            'displayName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'fullName' => $this->getReference('oro_calendar:user:foo_user_2')->getFullName(),
                            'email' => 'foo_user_2@example.com',
                            'userId' => $this->getReference('oro_calendar:user:foo_user_2')->getId(),
                            'status' => Attendee::STATUS_ACCEPTED,
                            'type' => Attendee::TYPE_REQUIRED,
                            'createdAt' => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                                ->getCreatedAt()->format(DATE_RFC3339),
                            'updatedAt' => $newEvent->getAttendeeByEmail('foo_user_2@example.com')
                                ->getUpdatedAt()->format(DATE_RFC3339),
                        ]
                    ],
                    'editable' => false,
                    'removable' => false,
                    'backgroundColor' => null,
                    'invitationStatus' => Attendee::STATUS_ACCEPTED,
                    'recurringEventId' => null,
                    'originalStart' => null,
                    'isCancelled' => false,
                    'createdAt' => $newChildEvent->getCreatedAt()->format(DATE_RFC3339),
                    'updatedAt' => $newChildEvent->getUpdatedAt()->format(DATE_RFC3339),
                    'isOrganizer' => false,
                    'organizerDisplayName' => 'Billy Wilf',
                    'organizerEmail' => 'foo_user_1@example.com',
                    'organizerUserId' => $this->getOrganizerId($newChildEvent)
                ],
            ],
            $response,
            false
        );
    }

    /**
     * @param CalendarEvent $newChildEvent
     * @return null|string
     */
    private function getOrganizerId(CalendarEvent $newChildEvent)
    {
        return $newChildEvent->getOrganizerUser() ? (string)$newChildEvent->getOrganizerUser()->getId() : null;
    }
}
