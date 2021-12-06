<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarConnectionControllerTest extends WebTestCase
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
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-04T11:29:46+00:00',
            'subordinate' => false
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEmpty($result);
    }

    public function testPostCalendarEvent()
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
                    'email'       => $adminUser->getEmail(),
                    'status'      => null,
                ],
                [
                    'displayName' => sprintf('%s %s', $user->getFirstName(), $user->getLastName()),
                    'email'       => $user->getEmail(),
                    'status'      => null,
                ],
            ],
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => ['wednesday'],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => '2016-05-04T11:29:46+00:00',
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $request);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
    }

    /**
     * @depends testPostCalendarEvent
     */
    public function testGetsAfterPost()
    {
        $user = $this->getReference('oro_calendar:user:system_user_1');
        $admin = $this->getAdminUser();

        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => true
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->assertEquals(
            [
                'title'            => 'Test Event',
                'description'      => 'Test Description',
                'start'            => '2016-05-04T11:29:46+00:00',
                'end'              => '2016-05-04T11:29:46+00:00',
                'allDay'           => true,
                'calendarAlias'    => 'user',
                'attendees'        => [
                    [
                        'displayName' => $user->getFullName(),
                        'fullName'    => $user->getFullName(),
                        'email'       => $user->getEmail(),
                        'status'      => 'none',
                        'type'        => 'required',
                        'userId'      => $user->getId(),
                    ],
                    [
                        'displayName' => $admin->getFullName(),
                        'fullName'    => $admin->getFullName(),
                        'email'       => $admin->getEmail(),
                        'status'      => 'none',
                        'type'        => 'required',
                        'userId'      => $admin->getId(),
                    ]
                ],
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_WEEKLY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => ['wednesday'],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
            ],
            $this->extractInterestingResponseData($result[0])
        );
    }

    /**
     * @depends testGetsAfterPost
     */
    public function testAddConnection()
    {
        $user = $this->getReference('oro_calendar:user:system_user_1');

        $request = [
            'calendar'        => $this->getCalendar($user)->getId(),
            'calendarAlias'   => 'user',
            'targetCalendar'  => self::DEFAULT_USER_CALENDAR_ID,
            'visible'         => true,
        ];
        $this->client->request('POST', $this->getUrl('oro_api_post_calendar_connection'), $request);

        $this->getJsonResponseContent($this->client->getResponse(), 201);
    }

    /**
     * @depends testAddConnection
     */
    public function testGetsAfterAddConnection()
    {
        $user = $this->getReference('oro_calendar:user:system_user_1');
        $admin = $this->getAdminUser();

        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-05-03T11:29:46+00:00',
            'end'         => '2016-05-05T11:29:46+00:00',
            'subordinate' => true
        ];

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(2, $result);
        $this->assertNotEquals($result[0]['calendar'], $result[1]['calendar']);

        $expectedCalendarEvent = [
            'title'            => 'Test Event',
            'description'      => 'Test Description',
            'start'            => '2016-05-04T11:29:46+00:00',
            'end'              => '2016-05-04T11:29:46+00:00',
            'allDay'           => true,
            'calendarAlias'    => 'user',
            'attendees'        => [
                [
                    'displayName' => $user->getFullName(),
                    'fullName'    => $user->getFullName(),
                    'email'       => $user->getEmail(),
                    'status'      => 'none',
                    'type'        => 'required',
                    'userId'      => $user->getId(),
                ],
                [
                    'displayName' => $admin->getFullName(),
                    'fullName'    => $admin->getFullName(),
                    'email'       => $admin->getEmail(),
                    'status'      => 'none',
                    'type'        => 'required',
                    'userId'      => $admin->getId(),
                ]
            ],
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_WEEKLY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => ['wednesday'],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => '2016-05-04T11:29:46+00:00',
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
        ];

        $expectedCalendarEvents = [
            $expectedCalendarEvent,
            $expectedCalendarEvent,
        ];

        $this->assertEquals(
            $expectedCalendarEvents,
            [
                $this->extractInterestingResponseData($result[0]),
                $this->extractInterestingResponseData($result[1])
            ]
        );
    }

    private function extractInterestingResponseData(array $responseData): array
    {
        $result = array_intersect_key(
            $responseData,
            [
                'title'         => null,
                'description'   => null,
                'start'         => null,
                'end'           => null,
                'allDay'        => null,
                'attendees'     => null,
                'calendarAlias' => null,
                'recurrence'    => null,
            ]
        );

        $attendees = $result['attendees'];
        usort(
            $attendees,
            function ($user1, $user2) {
                return strcmp($user1['displayName'], $user2['displayName']);
            }
        );

        foreach ($attendees as &$attendee) {
            unset($attendee['createdAt'], $attendee['updatedAt']);
        }
        unset($attendee);

        $result['attendees'] = $attendees;
        unset($result['recurrence']['id']);

        return $result;
    }

    private function getAdminUser(): User
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);
    }

    private function getCalendar(User $user): Calendar
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(Calendar::class)
            ->findOneBy(['owner' => $user]);
    }
}
