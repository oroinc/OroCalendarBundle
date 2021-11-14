<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\MatchingEventsManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

class MatchingEventsManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const UID = '17f409b2-7393-42b1-9976-3394d9f5302e';
    private const EMAIL = 'email@oroinc.com';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var CalendarEventRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var MatchingEventsManager */
    private $manager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CalendarEventRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(CalendarEvent::class)
            ->willReturn($this->repository);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(CalendarEvent::class)
            ->willReturn($this->entityManager);

        $this->manager = new MatchingEventsManager($doctrineHelper);
    }

    public function testSkipIfEventIsNotNew()
    {
        $event = $this->getCalendarEvent(['id' => 1]);

        $this->repository->expects($this->never())
            ->method('findEventsWithMatchingUidAndOrganizer');

        $this->manager->onEventUpdate($event);
    }

    public function testMergeEvents()
    {
        $ownerB = $this->getEntity(User::class, ['email' => 'second@oroinc.com']);
        $ownerC = $this->getEntity(User::class, ['email' => 'third@oroinc.com']);
        $calendarB = $this->getEntity(Calendar::class, ['owner' => $ownerB]);
        $calendarC = $this->getEntity(Calendar::class, ['owner' => $ownerC]);

        $eventA = $this->getCalendarEvent([
            'isOrganizer' => true,
            'organizerEmail' => self::EMAIL,
            'uid' => self::UID,
        ]);

        $attendeeB = new Attendee();
        $attendeeB->setEmail('second@oroinc.com');

        $attendeeC = new Attendee();
        $attendeeC->setEmail('third@oroinc.com');

        $eventA->addAttendee($attendeeB)
            ->addAttendee($attendeeC);

        $eventB = $this->getCalendarEvent(
            [
                'id' => 1,
                'parent' => null,
                'isOrganizer' => false,
                'calendar' => $calendarB,
            ]
        );
        $eventC = $this->getCalendarEvent(
            [
                'id' => 2,
                'parent' => null,
                'isOrganizer' => false,
                'calendar' => $calendarC,
            ]
        );

        $this->repository->expects($this->once())
            ->method('findEventsWithMatchingUidAndOrganizer')
            ->with($eventA)
            ->willReturn([$eventB, $eventC]);

        $this->manager->onEventUpdate($eventA);

        $this->assertSame($eventA, $eventB->getParent());
        $this->assertSame($eventA, $eventC->getParent());
        $this->assertSame($attendeeB, $eventB->getRelatedAttendee());
        $this->assertSame($attendeeC, $eventC->getRelatedAttendee());
        $this->assertSame($eventA, $eventB->getRelatedAttendee()->getCalendarEvent());
        $this->assertSame($eventA, $eventC->getRelatedAttendee()->getCalendarEvent());
        $this->assertCount(2, $eventA->getAttendees());
    }

    public function testDoNotMergeEvents()
    {
        $ownerB = $this->getEntity(User::class, ['email' => 'second@oroinc.com']);
        $ownerC = $this->getEntity(User::class, ['email' => 'third@oroinc.com']);
        $calendarB = $this->getEntity(Calendar::class, ['owner' => $ownerB]);
        $calendarC = $this->getEntity(Calendar::class, ['owner' => $ownerC]);

        $attendeeB = new Attendee();
        $attendeeB->setEmail('second@oroinc.com');

        $attendeeC = new Attendee();
        $attendeeC->setEmail('third@oroinc.com');

        $eventB = $this->getCalendarEvent(
            [
                'id' => 1,
                'uid'   => self::UID,
                'parent' => null,
                'isOrganizer' => false,
                'calendar' => $calendarB,
            ]
        );
        $eventB->addAttendee($attendeeB)->addAttendee($attendeeC);

        $eventC = $this->getCalendarEvent(
            [
                'uid'   => self::UID,
                'parent' => null,
                'isOrganizer' => false,
                'calendar' => $calendarC,
            ]
        );
        $eventC->addAttendee($attendeeB)->addAttendee($attendeeC);

        $this->repository->expects($this->never())
            ->method('findEventsWithMatchingUidAndOrganizer');

        $this->manager->onEventUpdate($eventC);

        $this->assertNull($eventB->getParent());
        $this->assertNull($eventC->getParent());
        $this->assertNull($eventB->getRelatedAttendee());
        $this->assertNull($eventC->getRelatedAttendee());
        $this->assertCount(2, $eventB->getAttendees());
        $this->assertCount(2, $eventC->getAttendees());
        $this->assertEquals($eventB->getAttendees(), $eventC->getAttendees());
    }

    public function testMergeOnlyEventsWithAssignedCalendar()
    {
        $ownerC = $this->getEntity(User::class, ['email' => 'third@oroinc.com']);
        $calendarC = $this->getEntity(Calendar::class, ['owner' => $ownerC]);

        $eventA = $this->getCalendarEvent([
            'isOrganizer' => true,
            'organizerEmail' => self::EMAIL,
            'uid' => self::UID,
        ]);

        $attendeeB = new Attendee();
        $attendeeB->setEmail('second@oroinc.com');

        $attendeeC = new Attendee();
        $attendeeC->setEmail('third@oroinc.com');

        $eventA->addAttendee($attendeeB)
            ->addAttendee($attendeeC);

        $eventB = $this->getCalendarEvent(
            [
                'id' => 1,
                'parent' => null,
                'isOrganizer' => false,
            ]
        );
        $eventC = $this->getCalendarEvent(
            [
                'id' => 2,
                'parent' => null,
                'isOrganizer' => false,
                'calendar' => $calendarC,
            ]
        );

        $this->repository->expects($this->once())
            ->method('findEventsWithMatchingUidAndOrganizer')
            ->with($eventA)
            ->willReturn([$eventB, $eventC]);

        $this->manager->onEventUpdate($eventA);

        $this->assertSame($eventA, $eventC->getParent());
        $this->assertNull($eventB->getParent());
        $this->assertSame($attendeeC, $eventC->getRelatedAttendee());
        $this->assertNull($eventB->getRelatedAttendee());
        $this->assertSame($eventA, $eventC->getRelatedAttendee()->getCalendarEvent());
        $this->assertCount(2, $eventA->getAttendees());
    }

    /**
     * Case:
     * User A - organizer (without account in ORO) "email": "owner@example.com"
     * User B - registered user in oro "email": "second@oroinc.com",
     * User C - registered user in oro "email": "third@oroinc.com"
     *
     * 1. User A create an event in an external system and add as attendees User B and User C
     * 2. User B sync an event into the ORO (no organizerUser set at this point)
     * 3. User C sync an event with ORO (no organizerUser set at this point)
     * 4. User A creates an account in ORO
     * 5. User A sync events into the ORO (organizerUser set in all events)
     */
    public function testCopyOrganizerUserAttendeesAreRemovedFromChildEventsInCaseOfMerging()
    {
        $ownerB = $this->getEntity(User::class, ['email' => 'second@oroinc.com']);
        $ownerC = $this->getEntity(User::class, ['email' => 'third@oroinc.com']);
        $calendarB = $this->getEntity(Calendar::class, ['owner' => $ownerB]);
        $calendarC = $this->getEntity(Calendar::class, ['owner' => $ownerC]);

        $attendeeA1 = new Attendee();
        $attendeeA1->setEmail('second@oroinc.com');
        $attendeeA2 = new Attendee();
        $attendeeA2->setEmail('third@oroinc.com');
        $attendeeB1 = new Attendee();
        $attendeeB1->setEmail('second@oroinc.com');
        $attendeeB2 = new Attendee();
        $attendeeB2->setEmail('third@oroinc.com');
        $attendeeC1 = new Attendee();
        $attendeeC1->setEmail('second@oroinc.com');
        $attendeeC2 = new Attendee();
        $attendeeC2->setEmail('third@oroinc.com');

        $eventA = $this->getCalendarEvent([
            'isOrganizer' => true,
            'organizerEmail' => self::EMAIL,
            'uid' => self::UID,
            'organizerUser' => new User()
        ]);
        $eventA->addAttendee($attendeeA1)->addAttendee($attendeeA2);

        $eventB = $this->getCalendarEvent(
            [
                'id' => 1,
                'parent' => null,
                'isOrganizer' => false,
                'calendar' => $calendarB,
            ]
        );
        $eventB->addAttendee($attendeeB1)->addAttendee($attendeeB2);

        $eventC = $this->getCalendarEvent(
            [
                'id' => 2,
                'parent' => null,
                'isOrganizer' => false,
                'calendar' => $calendarC,
            ]
        );
        $eventC->addAttendee($attendeeC1)->addAttendee($attendeeC2);

        $this->repository->expects($this->once())
            ->method('findEventsWithMatchingUidAndOrganizer')
            ->with($eventA)
            ->willReturn([$eventB, $eventC]);

        // need to check if remove has been called for both events 2 times (as we have 2 attendee in each)
        $this->entityManager->expects($this->exactly(4))
            ->method('remove');

        $this->manager->onEventUpdate($eventA);

        $this->assertCount(2, $eventA->getAttendees());
        $this->assertInstanceOf(User::class, $eventA->getOrganizerUser());
        $this->assertCount(2, $eventB->getAttendees());
        $this->assertInstanceOf(User::class, $eventB->getOrganizerUser());
        $this->assertCount(2, $eventC->getAttendees());
        $this->assertInstanceOf(User::class, $eventC->getOrganizerUser());
    }

    private function getCalendarEvent(array $params): CalendarEvent
    {
        return $this->getEntity(CalendarEvent::class, $params);
    }
}
