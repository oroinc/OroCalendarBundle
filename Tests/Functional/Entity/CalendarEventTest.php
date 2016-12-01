<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class CalendarEventTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadUserData::class], true);
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

        $this->assertEmpty($entityManager->find('OroCalendarBundle:Attendee', $childCalendar->getId()));
    }

    /**
     * @param User $user
     *
     * @return Attendee
     */
    protected function createAttendee(User $user)
    {
        $attendee = new Attendee();
        $attendee->setDisplayName($user->getFullName());
        $attendee->setUser($user);
        $attendee->setEmail($user->getEmail());

        return $attendee;
    }

    /**
     * @param Calendar $calendar
     * @param string $title
     * @param CalendarEvent $parent
     * @param Attendee[] $attendees
     *
     * @return CalendarEvent
     */
    protected function createCalendarEvent(
        Calendar $calendar,
        $title,
        CalendarEvent $parent = null,
        array $attendees = []
    ) {
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

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCalendarBundle:CalendarEvent');
    }
}
