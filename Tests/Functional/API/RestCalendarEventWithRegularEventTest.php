<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventWithRegularEventTest extends WebTestCase
{
    const DATE_RANGE_START = '-5 day';
    const DATE_RANGE_END = '+5 day';

    const DEFAULT_USER_CALENDAR_ID = 1;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            LoadCalendarEventData::class,
            LoadActivityTargets::class,
            LoadUserData::class,
        ]);
    }

    /**
     * Creates regular event.
     *
     * @return array
     */
    public function testPostRegularEvent()
    {
        $parameters = $this->getRegularEventParameters();
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $parameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $this->assertNotEmpty($result['uid']);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);

        return ['id' => $result['id'], 'regularEventParameters' => $parameters];
    }

    /**
     * Reads regular event.
     *
     * @depends testPostRegularEvent
     */
    public function testGetRegularEvent(array $data)
    {
        $id = $data['id'];

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);

        foreach ($data['regularEventParameters'] as $attribute => $value) {
            $this->assertArrayHasKey($attribute, $result);
            $this->assertEquals(
                $value,
                $result[$attribute],
                sprintf('Failed assertion for $result["%s"] value: ', $attribute)
            );
        }
    }

    /**
     * Updates regular event.
     *
     * @depends testPostRegularEvent
     */
    public function testPutRegularEvent(array $data)
    {
        $id = $data['id'];
        $parameters = $data['regularEventParameters'];
        $parameters['title'] = 'Test Regular Event Updated';
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $id]),
            $parameters
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($id);
        $this->assertEquals($parameters['title'], $event->getTitle());
    }

    /**
     * Deletes regular event.
     *
     * @depends testPostRegularEvent
     */
    public function testDeleteRegularEvent(array $data)
    {
        $id = $data['id'];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $id])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $id]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
    }

    public function testCgetByDateRangeFilter()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => gmdate(DATE_RFC3339, strtotime(self::DATE_RANGE_START)),
            'end' => gmdate(DATE_RFC3339, strtotime(self::DATE_RANGE_END)),
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(18, $result);
    }

    public function testCgetByDateRangeFilterWithSummerWinterTimeChecking()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => date_create('2016-01-21 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'end' => date_create('2016-01-23 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result);
        $this->assertEquals($result[0]['start'], '2016-01-21T04:00:00+00:00');
        $this->assertEquals($result[0]['end'], '2016-01-21T05:00:00+00:00');

        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'start' => date_create('2016-06-21 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'end' => date_create('2016-06-23 00:00:00', new \DateTimeZone('UTC'))->format(DATE_RFC3339),
            'subordinate' => false
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result);
        $this->assertEquals($result[0]['start'], '2016-06-21T03:00:00+00:00');
        $this->assertEquals($result[0]['end'], '2016-06-21T04:00:00+00:00');
    }

    public function testCgetByPagination()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'page' => 1,
            'limit' => 10,
            'subordinate' => false
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(10, $result);
    }

    public function testCgetByPaginationWithRecurringEventIdFilter()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'page' => 1,
            'limit' => 100,
            'subordinate' => false,
            'recurringEventId' => $this->getReference('eventInRangeWithCancelledException')->getId(),
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result);
    }

    public function testGetListOfEventsByUidFilter()
    {
        $request = array(
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'page' => 1,
            'limit' => 100,
            'subordinate' => false,
        );
        $uid = 'b139fecc-41cf-478d-8f8e-b6122f491ace';
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&uid=' . urlencode($uid)
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $result);
        $this->assertEquals($uid, reset($result)['uid']);
    }

    public function testGetByCalendar()
    {
        $id = $this->getReference('eventInRangeWithCancelledException')->getId();
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
     * @return array
     */
    protected function getRegularEventParameters()
    {
        return [
            'title' => 'Test Regular Event',
            'description' => 'Test Regular Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
        ];
    }
}
