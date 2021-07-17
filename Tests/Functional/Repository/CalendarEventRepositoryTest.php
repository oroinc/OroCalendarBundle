<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Repository;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventRepositoryTest extends WebTestCase
{
    const DUPLICATED_UID = 'b139fecc-41cf-478d-8f8e-b6122f491ace';
    const MATCHING_UID = '1acb93ce-c54a-11e7-abc4-cec278b6b50a';

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCalendarEventData::class]);
    }

    public function testFindDuplicateForCalendarEventWithoutId()
    {
        $repository = $this->getRepository();
        $calendar = $this->getCalendar();

        $calendarEvent = new CalendarEvent();
        $calendarEvent
            ->setUid(static::DUPLICATED_UID);

        $events = $repository->findDuplicatedEvent($calendarEvent, $calendar->getId());

        $this->assertCount(1, $events);

        $eventFromDb = $events[0];

        $this->assertEquals(static::DUPLICATED_UID, $eventFromDb->getUid());
        $this->assertEquals($calendar->getId(), $eventFromDb->getCalendar()->getId());
    }

    public function testDoesNotFindCalendarEventItselfWhenLookingForDuplicates()
    {
        $repository = $this->getRepository();
        /** @var CalendarEvent $calendarEvent */
        $calendarEvent = $repository->findOneBy(['uid' => static::DUPLICATED_UID]);

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

    private function getDoctrineHelper(): DoctrineHelper
    {
        return $this->getContainer()->get('oro_entity.doctrine_helper');
    }

    private function getRepository(): CalendarEventRepository
    {
        return $this->getDoctrineHelper()->getEntityRepository(CalendarEvent::class);
    }

    private function getCalendar(): Calendar
    {
        $doctrineHelper = $this->getDoctrineHelper();
        $user = $doctrineHelper->getEntityRepository(User::class)->findOneByUsername('admin');
        $organization = $doctrineHelper->getEntityRepository(Organization::class)->getFirst();

        /** @var CalendarRepository $calendarRepo */
        $calendarRepo = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepository(Calendar::class);

        return $calendarRepo->findDefaultCalendar($user->getId(), $organization->getId());
    }
}
