<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence as RecurrenceModel;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventWithRecurrentEventTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            LoadCalendarEventData::class,
            LoadActivityTargets::class,
            LoadUserData::class,
        ]);
    }

    public function testPostRecurringEvent(): array
    {
        $recurringEventParameters = $this->getRecurringEventParameters();
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        /** @var CalendarEvent $event */
        $event = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->find($result['id']);
        $this->assertNotNull($event);
        $this->assertEquals(
            RecurrenceModel::MAX_END_DATE,
            $event->getRecurrence()->getCalculatedEndTime()->format(DATE_RFC3339)
        );

        $activityTargetEntities = $event->getActivityTargets();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );

        return ['id' => $result['id'], 'recurrenceId' => $event->getRecurrence()->getId()];
    }

    /**
     * @depends testPostRecurringEvent
     */
    public function testGetRecurringEvent(array $data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['recurrenceId'], $result['recurrence']['id']);
    }

    /**
     * Updates recurring event. The goal is to test transformation from recurring event to regular one.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     */
    public function testPutRecurringEvent()
    {
        $recurringEventParameters = $this->getRecurringEventParameters();
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();

        $recurringEventParameters['recurrence']['interval'] = '0';
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );
        $this->getJsonResponseContent($this->client->getResponse(), 400);

        $recurringEventParameters['recurrence'] = 'test_string'; //should be validated
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );
        $this->getJsonResponseContent($this->client->getResponse(), 400);

        $recurringEventParameters['recurrence'] = ''; // recurring event will become regular event.
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEquals(Attendee::STATUS_NONE, $result['invitationStatus']);

        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->findOneBy(['id' => $data['id']]);
        $activityTargetEntities = $event->getActivityTargets();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );
        $this->assertNull($event->getRecurrence());
        $recurrence = $this->getContainer()->get('doctrine')->getRepository(Recurrence::class)
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($event);
        $em->flush();
    }

    /**
     * Deletes recurring event.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     */
    public function testDeleteRecurringEvent()
    {
        $recurringEventParameters = $this->getRecurringEventParameters();
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $recurrence = $this->getContainer()->get('doctrine')->getRepository(Recurrence::class)
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
    }

    /**
     * @depends testPostRecurringEvent
     */
    public function testPostRecurringEventException(array $data): array
    {
        $recurringEventExceptionParameters = $this->getRecurringEventExceptionParameters();
        $recurringEventExceptionParameters['recurringEventId'] = $data['id'];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            $recurringEventExceptionParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $exception = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->find($result['id']);
        $this->assertNotNull($exception);
        $this->assertEquals($data['id'], $exception->getRecurringEvent()->getId());
        $activityTargetEntities = $exception->getActivityTargets();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );

        return [
            'id' => $result['id'],
            'recurringEventId' => $data['id'],
            'recurringEventExceptionParameters' => $recurringEventExceptionParameters,
        ];
    }

    /**
     * @depends testPostRecurringEventException
     */
    public function testGetRecurringEventException(array $data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        foreach ($data['recurringEventExceptionParameters'] as $attribute => $value) {
            if ($attribute === 'attendees') {
                continue;
            }

            $this->assertArrayHasKey($attribute, $result);
            $this->assertEquals(
                $value,
                $result[$attribute],
                sprintf('Failed assertion for $result["%s"] value: ', $attribute)
            );
        }
    }

    /**
     * @depends testPostRecurringEventException
     */
    public function testPutRecurringEventException(array $data)
    {
        $recurringEventExceptionParameters = $data['recurringEventExceptionParameters'];
        $recurringEventExceptionParameters['recurringEventId'] = $data['recurringEventId'];
        $recurringEventExceptionParameters['isCancelled'] = false;
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventExceptionParameters
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->find($data['id']);
        $this->assertCount(1, $event->getChildEvents());
        $this->assertNotNull($event->getRecurringEvent());
        $this->assertCount(1, $event->getRecurringEvent()->getChildEvents());
        $this->assertEquals(
            $event->getRecurringEvent()->getChildEvents()->first()->getId(),
            $event->getChildEvents()->first()->getRecurringEvent()->getId()
        );
        $this->assertEquals($recurringEventExceptionParameters['isCancelled'], $event->isCancelled());
        $activityTargetEntities = $event->getActivityTargets();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );
    }

    /**
     * @depends testPostRecurringEventException
     */
    public function testDeleteRecurringEventException(array $data)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
    }

    /**
     * Deletes recurring event with exception to ensure that all related entities are cascade removed.
     * Dependencies where not injected to handle with new objects.
     */
    public function testDeleteRecurringEventWithException()
    {
        $recurringEventExceptionParameters = $this->getRecurringEventExceptionParameters();
        $recurringEventParameters = $this->getRecurringEventParameters();

        // creates new recurring event
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();

        // creates new recurring event exception
        $recurringEventExceptionParameters['recurringEventId'] = $data['id'];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            $recurringEventExceptionParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $data['exceptionId'] = $result['id'];

        // deletes recurring events and all related entities
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        // ensures that all related entities are deleted
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $recurrence = $this->getContainer()->get('doctrine')->getRepository(Recurrence::class)
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
        $exception = $this->getContainer()->get('doctrine')->getRepository(CalendarEvent::class)
            ->findOneBy(['id' => $data['exceptionId']]);
        $this->assertNull($exception);
    }

    public function testPostRecurringEventWithTimeZone(): array
    {
        $recurringEventParameters = $this->getRecurringEventParameters();
        $recurringEvents = $recurringEventParameters;
        $recurringEvents['recurrence']['timeZone'] = 'America/Santa_Isabel';
        $recurringEvents['recurrence']['interval'] = '0'; //check that it will be validated
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $recurringEvents);
        $this->getJsonResponseContent($this->client->getResponse(), 400);

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            $recurringEventParameters
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        /** @var CalendarEvent $event */
        $event = $this->getContainer()->get('doctrine')
            ->getRepository(CalendarEvent::class)
            ->find($result['id']);
        $this->assertNotNull($event);
        $this->assertEquals(
            RecurrenceModel::MAX_END_DATE,
            $event->getRecurrence()->getCalculatedEndTime()->format(DATE_RFC3339)
        );

        return ['id' => $result['id'], 'recurrenceId' => $event->getRecurrence()->getId()];
    }

    /**
     * @depends testPostRecurringEventWithTimeZone
     */
    public function testGetRecurringEventWithTimeZone(array $data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['recurrenceId'], $result['recurrence']['id']);
    }

    private function getRecurringEventParameters(): array
    {
        $targetOne = $this->getReference('activity_target_one');

        return [
            'title' => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
            'recurrence' => [
                'recurrenceType' => RecurrenceModel::TYPE_DAILY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => [],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => gmdate(DATE_RFC3339),
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
            'attendees' => [
                [
                    'email' => 'system_user_2@example.com',
                ],
            ],
            'contexts' => json_encode([
                'entityId' => $targetOne->getId(),
                'entityClass' => get_class($targetOne),
            ], JSON_THROW_ON_ERROR)
        ];
    }

    private function getRecurringEventExceptionParameters(): array
    {
        return [
            'title' => 'Test Recurring Event Exception',
            'description' => 'Test Recurring Exception Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
            'recurringEventId' => -1, // is set dynamically
            'originalStart' => gmdate(DATE_RFC3339),
            'isCancelled' => true,
            'attendees' => [
                [
                    'email' => 'system_user_2@example.com',
                ],
            ],
        ];
    }
}
