<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Exception\NotUniqueAttendeeException;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\MatchingEventsManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

use Oro\Component\Testing\Unit\EntityTrait;

class MatchingEventsManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const UID = '17f409b2-7393-42b1-9976-3394d9f5302e';
    const EMAIL = 'email@oroinc.com';

    /** @var CalendarEventRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var MatchingEventsManager */
    private $manager;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(CalendarEventRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(CalendarEvent::class)
            ->willReturn($this->repository);

        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(CalendarEvent::class)
            ->willReturn($entityManager);

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
     * @param array $params
     * @return CalendarEvent
     */
    private function getCalendarEvent(array $params): CalendarEvent
    {
        return $this->getEntity(CalendarEvent::class, $params);
    }
}
