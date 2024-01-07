<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventRepositoryTest extends WebTestCase
{
    private const DUPLICATED_UID = 'b139fecc-41cf-478d-8f8e-b6122f491ace';
    private const MATCHING_UID = '1acb93ce-c54a-11e7-abc4-cec278b6b50a';

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCalendarEventData::class, LoadUser::class]);
    }

    private function getRepository(): CalendarEventRepository
    {
        return $this->getDoctrine()->getRepository(CalendarEvent::class);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    private function getCalendar(): Calendar
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        return $this->getDoctrine()->getRepository(Calendar::class)
            ->findDefaultCalendar($user->getId(), $user->getOrganization()->getId());
    }

    public function testFindDuplicateForCalendarEventWithoutId()
    {
        $repository = $this->getRepository();
        $calendar = $this->getCalendar();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid(self::DUPLICATED_UID);

        $events = $repository->findDuplicatedEvent($calendarEvent, $calendar->getId());

        $this->assertCount(1, $events);

        $eventFromDb = $events[0];

        $this->assertEquals(self::DUPLICATED_UID, $eventFromDb->getUid());
        $this->assertEquals($calendar->getId(), $eventFromDb->getCalendar()->getId());
    }

    public function testDoesNotFindCalendarEventItselfWhenLookingForDuplicates()
    {
        $repository = $this->getRepository();
        /** @var CalendarEvent $calendarEvent */
        $calendarEvent = $repository->findOneBy(['uid' => self::DUPLICATED_UID]);

        $this->assertNotNull($calendarEvent);

        $events = $repository->findDuplicatedEvent($calendarEvent, $calendarEvent->getCalendar()->getId());
        $this->assertCount(0, $events);
    }

    public function testFindEventsWithMatchingUidAndOrganizer()
    {
        $repository = $this->getRepository();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid(self::MATCHING_UID)
            ->setOrganizerEmail('organizer@oro.com');

        $events = $repository->findEventsWithMatchingUidAndOrganizer($calendarEvent);

        $this->assertCount(1, $events);
    }

    public function testCantFindEventsWithMatchingUidAndOrganizer()
    {
        $repository = $this->getRepository();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid(self::MATCHING_UID)
            ->setOrganizerEmail('not-organizer@oro.com');

        $events = $repository->findEventsWithMatchingUidAndOrganizer($calendarEvent);

        $this->assertCount(0, $events);
    }

    public function testCantFindMatchingEventsBecauseLackOfUid()
    {
        $repository = $this->getRepository();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setOrganizerEmail('organizer@oro.com');

        $events = $repository->findEventsWithMatchingUidAndOrganizer($calendarEvent);

        $this->assertCount(0, $events);
    }

    public function testCantFindMatchingEventsBecauseLackOfOrganizerEmail()
    {
        $repository = $this->getRepository();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid(self::MATCHING_UID);

        $events = $repository->findEventsWithMatchingUidAndOrganizer($calendarEvent);

        $this->assertCount(0, $events);
    }
}
