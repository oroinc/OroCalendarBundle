<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventWithAttendeesTest extends WebTestCase
{
    private const DEFAULT_USER_CALENDAR_ID = 1;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadUserData::class]);
    }

    public function testGets()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => date(DATE_RFC3339, strtotime('-1 day')),
            'end'         => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => false,
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEmpty($result);
    }

    public function testPost(): int
    {
        $user = $this->getReference('oro_calendar:user:system_user_1');

        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => [
                [
                    'email'  => $adminUser->getEmail(),
                    'status' => null,
                    'type'   => Attendee::TYPE_ORGANIZER,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => null,
                ],
                [
                    'displayName' => 'attendee without email',
                    'type'        => Attendee::TYPE_OPTIONAL,
                ],
                [
                    'displayName' => 'attendee with email and with unknown type',
                    'email'       => 'unknown-type@email.com',
                    'type'        => 'unknown_type',
                ],
                [
                    'displayName' => 'attendee with email and with type = null',
                    'email'       => 'type-null@email.com',
                    'type'        => null,
                ],
                [
                    'displayName' => 'attendee without email and with type = null',
                    'type'        => null,
                ],
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @depends testPost
     */
    public function testGetAfterPost(int $id)
    {
        $user = $this->getReference('oro_calendar:user:system_user_1');
        $adminUser = $this->getAdminUser();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }
        unset($attendee);

        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'invitationStatus' => 'none',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => 'system_user_1@example.com',
                        'status'      => 'none',
                        'type'        => 'required',
                        'userId'      => $user->getId(),
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => 'admin@example.com',
                        'status'      => 'none',
                        'type'        => Attendee::TYPE_ORGANIZER,
                        'userId'      => $adminUser->getId(),
                    ],
                    [
                        'displayName' => 'attendee with email and with type = null',
                        'email'       => 'type-null@email.com',
                        'type'        => Attendee::TYPE_REQUIRED,
                        'userId'      => null,
                        'status'      => 'none',
                    ],
                    [
                        'displayName' => 'attendee with email and with unknown type',
                        'email'       => 'unknown-type@email.com',
                        'userId'      => null,
                        'status'      => 'none',
                        'type'        => Attendee::TYPE_REQUIRED,
                    ],
                    [
                        'displayName' => 'attendee without email',
                        'email'       => null,
                        'userId'      => null,
                        'status'      => 'none',
                        'type'        => Attendee::TYPE_OPTIONAL,
                    ],
                    [
                        'displayName' => 'attendee without email and with type = null',
                        'type'        => Attendee::TYPE_REQUIRED,
                        'email'       => null,
                        'userId'      => null,
                        'status'      => 'none',
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(6, $attendees);

        $admin = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'admin@example.com';
            }
        )->first();
        $this->assertEquals('admin@example.com', $admin->getEmail());
        $this->assertEquals('admin', $admin->getUser()->getUsername());
        $this->assertEquals($admin, $calendarEvent->getRelatedAttendee());

        $simpleUser = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'system_user_1@example.com';
            }
        )->first();
        $this->assertEquals('system_user_1@example.com', $simpleUser->getEmail());
        $this->assertEquals('system_user_1', $simpleUser->getUser()->getUsername());
    }

    /**
     * @depends testPost
     */
    public function testPut(int $id): int
    {
        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'title'           => 'Test Event Updated',
            'description'     => 'Test Description Updated',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => [
                [
                    'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                    'email'       => $adminUser->getEmail(),
                    'status'      => null,
                ],
                [
                    'displayName' => 'Ext',
                    'email'       => 'ext@example.com',
                    'status'      => 'tentative',
                    'type'        => 'organizer',
                ],
            ],
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(Attendee::STATUS_NONE, $result['invitationStatus']);

        return $id;
    }

    /**
     * @depends testPut
     */
    public function testGetAfterPut(int $id)
    {
        $adminUser = $this->getAdminUser();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }
        unset($attendee);

        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event Updated',
                'description'      => 'Test Description Updated',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'invitationStatus' => 'none',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'attendees'        => [
                    [
                        'displayName' => 'Ext',
                        'email'       => 'ext@example.com',
                        'status'      => 'tentative',
                        'type'        => 'organizer',
                        'userId'      => null,
                    ],
                    [
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'email'       => $adminUser->getEmail(),
                        'status'      => 'none',
                        'type'        => 'required',
                        'userId'      => $adminUser->getId(),
                    ],
                ],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(2, $attendees);

        $boundAttendees = array_values(
            array_filter(
                array_map(
                    function (Attendee $attendee) {
                        return $attendee->getUser() ? $attendee : null;
                    },
                    $attendees->toArray()
                )
            )
        );

        $this->assertCount(1, $boundAttendees);
        $this->assertEquals('admin@example.com', $boundAttendees[0]->getEmail());
        $this->assertEquals('admin', $boundAttendees[0]->getUser()->getUsername());
        $this->assertEquals($boundAttendees[0], $calendarEvent->getRelatedAttendee());
    }

    /**
     * @depends testPut
     */
    public function testGetCreatedEvents()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => false,
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $result);

        foreach ($result[0]['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }
        unset($attendee, $result[0]['id']);

        $adminUser = $this->getAdminUser();

        $this->assertEquals(
            [
                [
                    'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                    'title'            => 'Test Event Updated',
                    'description'      => 'Test Description Updated',
                    'start'            => '2016-05-04T11:29:46+00:00',
                    'end'              => '2016-05-04T11:29:46+00:00',
                    'allDay'           => true,
                    'backgroundColor'  => '#FF0000',
                    'invitationStatus' => 'none',
                    'parentEventId'    => null,
                    'editable'         => true,
                    'removable'        => true,
                    'calendarAlias'    => 'user',
                    'attendees'        => [
                        [
                            'displayName' => 'Ext',
                            'email'       => 'ext@example.com',
                            'status'      => 'tentative',
                            'type'        => 'organizer',
                            'fullName'    => '',
                            'userId'      => null,
                        ],
                        [
                            'displayName' => 'John Doe',
                            'email'       => 'admin@example.com',
                            'status'      => 'none',
                            'type'        => 'required',
                            'fullName'    => 'John Doe',
                            'userId'      => $adminUser->getId(),
                        ],
                    ],
                ],
            ],
            [$this->extractInterestingResponseData($result[0])]
        );
    }

    /**
     * @depends testPut
     */
    public function testGetByCalendar(int $id)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_calendarevent_by_calendar',
                ['id' => self::DEFAULT_USER_CALENDAR_ID, 'eventId' => $id]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @depends testPut
     */
    public function testCget(int $id)
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => true,
        ];
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result[0]['id']);
    }

    /**
     * @depends testPut
     */
    public function testCgetFiltering()
    {
        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'page'        => 1,
            'limit'       => 10,
            'subordinate' => false,
        ];
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            .'&createdAt>'.urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            .'&createdAt>'.urlencode('2050-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($result);
    }

    /**
     * @depends testPut
     */
    public function testDelete($id)
    {
        // guard
        $this->assertNotNull(
            $this->getContainer()->get('doctrine')
                ->getRepository(CalendarEvent::class)
                ->find($id)
        );

        $this->client->request('DELETE', $this->getUrl('oro_api_get_calendarevent', ['id' => $id]));
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $this->getContainer()->get('doctrine')
            ->getManagerForClass(CalendarEvent::class)
            ->clear();

        $this->assertNull(
            $this->getContainer()->get('doctrine')
                ->getRepository(CalendarEvent::class)
                ->find($id)
        );
    }

    private function extractInterestingResponseData(array $responseData): array
    {
        $result = array_intersect_key(
            $responseData,
            [
                'id'               => null,
                'calendar'         => null,
                'title'            => null,
                'description'      => null,
                'start'            => null,
                'end'              => null,
                'allDay'           => null,
                'backgroundColor'  => null,
                'invitationStatus' => null,
                'parentEventId'    => null,
                'attendees'        => null,
                'editable'         => null,
                'removable'        => null,
                'calendarAlias'    => null,
            ]
        );

        $attendees = $result['attendees'];
        usort(
            $attendees,
            function ($user1, $user2) {
                return strcmp($user1['displayName'], $user2['displayName']);
            }
        );
        $result['attendees'] = $attendees;

        return $result;
    }

    public function testPostRemoveAttendees(): int
    {
        $user = $this->getReference('oro_calendar:user:system_user_1');

        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => [
                [
                    'email'  => $adminUser->getEmail(),
                    'status' => null,
                    'type'   => Attendee::TYPE_ORGANIZER,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => null,
                ],
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPostRemoveAttendees
     */
    public function testGetAfterPostRemoveAttendees(int $id)
    {
        $user = $this->getReference('oro_calendar:user:system_user_1');
        $adminUser = $this->getAdminUser();

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }
        unset($attendee);

        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'attendees'        => [
                    [
                        'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                        'email'       => $user->getEmail(),
                        'status'      => 'none',
                        'type'        => Attendee::TYPE_REQUIRED,
                        'userId'      => $user->getId(),
                    ],
                    [
                        'email'       => $adminUser->getEmail(),
                        'status'      => 'none',
                        'displayName' => sprintf('%s %s', $adminUser->getFirstName(), $adminUser->getLastName()),
                        'type'        => Attendee::TYPE_ORGANIZER,
                        'userId'      => $adminUser->getId(),
                    ],
                ],
                'invitationStatus' => 'none',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(2, $attendees);

        $admin = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'admin@example.com';
            }
        )->first();
        $this->assertEquals('admin@example.com', $admin->getEmail());
        $this->assertEquals('admin', $admin->getUser()->getUsername());
        $this->assertEquals($admin, $calendarEvent->getRelatedAttendee());

        $simpleUser = $attendees->filter(
            function ($element) {
                return $element->getEmail() && $element->getEmail() === 'system_user_1@example.com';
            }
        )->first();
        $this->assertEquals('system_user_1@example.com', $simpleUser->getEmail());
        $this->assertEquals('system_user_1', $simpleUser->getUser()->getUsername());
    }

    /**
     * @depends testPostRemoveAttendees
     */
    public function testPutRemoveAttendees(int $id): int
    {
        $request = [
            'attendees' => [],
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(Attendee::STATUS_NONE, $result['invitationStatus']);

        return $id;
    }

    /**
     * @depends testPutRemoveAttendees
     */
    public function testGetAfterPutRemoveAttendees(int $id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);

        foreach ($result['attendees'] as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }
        unset($attendee);

        $this->assertEquals(
            [
                'id'               => $id,
                'calendar'         => self::DEFAULT_USER_CALENDAR_ID,
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'backgroundColor'  => '#FF0000',
                'invitationStatus' => 'none',
                'parentEventId'    => null,
                'editable'         => true,
                'removable'        => true,
                'attendees'        => [],
            ],
            $this->extractInterestingResponseData($result)
        );

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->find($id);

        $attendees = $calendarEvent->getAttendees();
        $this->assertCount(0, $attendees);
    }

    public function testBindUserToAttendeeIsCaseInsensitive()
    {
        $this->getReference('oro_calendar:user:system_user_1')->setEmail('system_uSer_1@example.com');
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $user = $this->getReference('oro_calendar:user:system_user_1');

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => [
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => 'sYstem_user_1@example.com',
                    'status'      => null,
                ],
            ]
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        $calendarEvent = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->find($result['id']);

        $attendee = $calendarEvent->getAttendees()->first();
        $this->assertEquals($user->getId(), $attendee->getUser()->getId());
    }

    public function testPostWithNullAttendee(): int
    {
        $adminUser = $this->getAdminUser();

        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'id'              => null,
            'title'           => 'Test Event',
            'description'     => 'Test Description',
            'start'           => '2016-05-04T11:29:46+00:00',
            'end'             => '2016-05-04T11:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => [
                [
                    'email'  => $adminUser->getEmail(),
                    'status' => null,
                    'type'   => Attendee::TYPE_ORGANIZER,
                ],
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPostWithNullAttendee
     */
    public function testPutWithNullAttendee(int $id)
    {
        $request = [
            'calendar'        => self::DEFAULT_USER_CALENDAR_ID,
            'title'           => 'Test Event Updated',
            'description'     => 'Test Description Updated',
            'start'           => '2016-05-04T12:29:46+00:00',
            'end'             => '2016-05-04T12:29:46+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'attendees'       => '',
        ];
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $request
        );

        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    private function getAdminUser(): User
    {
        return $this->getContainer()->get('doctrine')->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);
    }
}
