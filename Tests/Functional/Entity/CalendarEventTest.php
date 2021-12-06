<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadUserData::class]);
    }

    public function testRelatedAttendeeShouldBeRemovedAfterChildEventIsRemoved()
    {
        $entityManager = $this->getEntityManager();

        $mainCalendar = $this->getReference('oro_calendar:calendar:system_user_1');
        $mainUser = $this->getReference('oro_calendar:user:system_user_1');

        $childCalendar = $this->getReference('oro_calendar:calendar:system_user_2');
        $childUser = $this->getReference('oro_calendar:user:system_user_2');

        $mainAttendee = $this->createAttendee($mainUser);
        $childAttendee = $this->createAttendee($childUser);

        $mainEvent = $this->createCalendarEvent(
            $mainCalendar,
            'parent event',
            null,
            [$mainAttendee, $childAttendee]
        );
        $entityManager->persist($mainEvent);

        $childEvent = $this->createCalendarEvent(
            $childCalendar,
            'child event',
            $mainEvent
        );
        $entityManager->persist($childEvent);

        $entityManager->flush();
        $entityManager->refresh($mainEvent);

        $this->assertCount(2, $mainEvent->getAttendees()->toArray());
        $this->assertSame($mainAttendee, $mainEvent->getRelatedAttendee());
        $this->assertSame($childAttendee, $childEvent->getRelatedAttendee());

        $entityManager->remove($childEvent);
        $entityManager->flush();
        $entityManager->clear();

        $this->assertEmpty($entityManager->find(Attendee::class, $childCalendar->getId()));
    }

    public function testUidIsGeneratedInCaseItIsNotProvided()
    {
        $event = new CalendarEvent();
        $event->setTitle('event title');
        $event->setStart(new \DateTime());
        $event->setEnd(new \DateTime());
        $event->setAllDay(false);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($event);
        $entityManager->flush();

        $eventFromDb = $entityManager->find(CalendarEvent::class, $event->getId());

        $this->assertNotNull($eventFromDb->getUid());
    }

    public function testUidIsNotOverwrittenInCaseItIsProvided()
    {
        $uid = 'b139fecc-41cf-478d-8f8e-b6122f491ace';
        $event = new CalendarEvent();
        $event->setTitle('event title');
        $event->setStart(new \DateTime());
        $event->setEnd(new \DateTime());
        $event->setUid($uid);
        $event->setAllDay(false);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($event);
        $entityManager->flush();

        $eventFromDb = $entityManager->find(CalendarEvent::class, $event->getId());

        $this->assertEquals($uid, $eventFromDb->getUid());
    }

    private function createAttendee(User $user): Attendee
    {
        $attendee = new Attendee();
        $attendee->setDisplayName($user->getFullName());
        $attendee->setUser($user);
        $attendee->setEmail($user->getEmail());

        return $attendee;
    }

    private function createCalendarEvent(
        Calendar $calendar,
        string $title,
        CalendarEvent $parent = null,
        array $attendees = []
    ): CalendarEvent {
        $event = new CalendarEvent();
        $event->setCalendar($calendar)
            ->setTitle($title)
            ->setStart(new \DateTime())
            ->setEnd(new \DateTime())
            ->setAllDay(true)
            ->setParent($parent);

        foreach ($attendees as $attendee) {
            $event->addAttendee($attendee);
        }

        $event->setRelatedAttendee($event->findRelatedAttendee());

        return $event;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(CalendarEvent::class);
    }
}
