<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Use \Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase instead of extending this.
 */
abstract class AbstractUseCaseTestCase extends WebTestCase
{
    protected const DEFAULT_USER_CALENDAR_ID = 1;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([LoadUserData::class]);
    }

    protected function assertCalendarEvents(array $expectedCalendarEvents, array $actualCalendarEvents): void
    {
        $this->assertCount(count($expectedCalendarEvents), $actualCalendarEvents, 'Calendar Events count mismatch');

        reset($actualCalendarEvents);
        foreach ($expectedCalendarEvents as $expectedEventData) {
            $actualEvent = current($actualCalendarEvents);

            if (isset($expectedEventData['attendees'])) {
                $expectedAttendeesData = $expectedEventData['attendees'];
                unset($expectedEventData['attendees']);

                $this->assertCount(
                    count($expectedAttendeesData),
                    $actualEvent['attendees'],
                    sprintf(
                        'Calendar Event Attendees count mismatch for calendar event.%s Expected: %s%s Actual: %s',
                        PHP_EOL,
                        json_encode($expectedEventData, JSON_THROW_ON_ERROR),
                        PHP_EOL,
                        json_encode($actualEvent, JSON_THROW_ON_ERROR)
                    )
                );

                reset($actualEvent['attendees']);
                foreach ($expectedAttendeesData as $expectedAttendeeData) {
                    $actualAttendee = current($actualEvent['attendees']);
                    $this->assertArraysPartiallyEqual(
                        $expectedAttendeeData,
                        $actualAttendee,
                        'Calendar Event Attendee'
                    );

                    next($actualEvent['attendees']);
                }
            }

            $this->assertArraysPartiallyEqual($expectedEventData, $actualEvent, 'Calendar Event');

            next($actualCalendarEvents);
        }
    }

    protected function assertArraysPartiallyEqual(array $expected, array $actual, string $entityAlias): void
    {
        foreach ($expected as $propertyName => $expectedValue) {
            $this->assertEquals(
                $expectedValue,
                $actual[$propertyName],
                sprintf(
                    '%s Property "%s" actual value does not match expected value.%s' .
                    'Expected data: %s.%sActual Data: %s',
                    $entityAlias,
                    $propertyName,
                    PHP_EOL,
                    json_encode($expected, JSON_THROW_ON_ERROR),
                    PHP_EOL,
                    json_encode($actual, JSON_THROW_ON_ERROR)
                )
            );
        }
    }

    protected function changeExpectedDataCalendarId(array $expectedCalendarEventsData, int $calendarId): array
    {
        foreach ($expectedCalendarEventsData as &$expectedCalendarEventData) {
            $expectedCalendarEventData['calendar'] = $calendarId;
        }

        return $expectedCalendarEventsData;
    }

    protected function assertCalendarEventAttendeesCount(int $eventId, int $expectedCount): void
    {
        /** we should clear doctrine cache to get real result */
        $this->getEntityManager()->clear();

        $calendarEvent = $this->getCalendarEventById($eventId);

        $this->assertCount($expectedCount, $calendarEvent->getAttendees()->toArray());
    }

    /**
     * Create new event
     */
    protected function addCalendarEventViaAPI(array $data): int
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $data);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    protected function updateCalendarEventViaAPI(int $calendarEventId, array $data): array
    {
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $calendarEventId]),
            $data
        );

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    protected function getCalendarEventViaAPI(int $calendarEventId): array
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $calendarEventId])
        );

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    protected function getOrderedCalendarEventsViaAPI(array $request): array
    {
        $result = $this->getAllCalendarEventsViaAPI($request);

        /**
         * For avoiding different element order in different DB`s
         */
        usort(
            $result,
            static fn (array $first, array $second) => date_create($second['start']) <=> date_create($first['start'])
        );

        return $result;
    }

    protected function getAllCalendarEventsViaAPI(array $request): array
    {
        $url = $this->getUrl('oro_api_get_calendarevents', $request);
        $this->client->request('GET', $url);

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    protected function deleteEventViaAPI(int $calendarEventId): void
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $calendarEventId])
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 204);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
    }

    /**
     * @return CalendarEvent[]
     */
    public function getRecurringCalendarEventsFromDB(): array
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->isNull('recurringEvent'));

        $calendarEvents = $this->getEntityManager()
            ->getRepository(CalendarEvent::class)
            ->matching($criteria);

        return $calendarEvents->toArray();
    }

    /**
     * @return CalendarEvent[]
     */
    public function getCalendarEventExceptionsFromDB(): array
    {
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->neq('recurringEvent', null));

        $calendarEvents = $this->getEntityManager()
            ->getRepository(CalendarEvent::class)
            ->matching($criteria);

        return $calendarEvents->toArray();
    }

    protected function getCalendarEventById(int $id): ?CalendarEvent
    {
        return $this->getEntityManager()->getRepository(CalendarEvent::class)->find($id);
    }

    protected function getAttendeeCalendarEvent(User $attendeeMappedUser, CalendarEvent $parentEvent): ?CalendarEvent
    {
        $calendar = $this->getUserCalendar($attendeeMappedUser);

        return $this->getEntityManager()
            ->getRepository(CalendarEvent::class)
            ->findOneBy(['parent' => $parentEvent, 'calendar' => $calendar]);
    }

    protected function getUserCalendar(User $user): ?Calendar
    {
        return $this->getEntityManager()
            ->getRepository(Calendar::class)
            ->getUserCalendarsQueryBuilder($user->getOrganization()->getId(), $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }
}
