<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\MatchingEventsManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateAttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateChildManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateExceptionManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Old tests moved after remove of \Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener\ChildEventsSubscriberTest.
 */
class CalendarEventManagerLegacyTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CalendarEventManager */
    private $calendarEventManager;

    protected function setUp(): void
    {
        $repository = $this->createMock(CalendarRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return new TestEnumValue($id, $id);
            });
        $repository->expects($this->any())
            ->method('findDefaultCalendars')
            ->willReturnCallback(function ($userIds) {
                return array_map(
                    function ($userId) {
                        return (new Calendar())
                            ->setOwner(new User($userId));
                    },
                    $userIds
                );
            });

        $calendarEventRepository = $this->createMock(CalendarEventRepository::class);
        $calendarEventRepository->expects($this->any())
            ->method('findEventsWithMatchingUidAndOrganizer')
            ->willReturn([]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                ['Extend\Entity\EV_Ce_Attendee_Status', null, $repository],
                ['Extend\Entity\EV_Ce_Attendee_Type', null, $repository],
                ['OroCalendarBundle:Calendar', null, $repository],
                [CalendarEvent::class, null, $calendarEventRepository],
            ]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($doctrine);

        $attendeeRelationManager = $this->createMock(AttendeeRelationManager::class);
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $calendarConfig = $this->createMock(SystemCalendarConfig::class);
        $attendeeManager = $this->createMock(AttendeeManager::class);
        $deleteManager = $this->createMock(DeleteManager::class);

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $updateManager = new UpdateManager(
            new UpdateAttendeeManager($attendeeRelationManager, $doctrine),
            new UpdateChildManager($doctrine),
            new UpdateExceptionManager($attendeeManager, $deleteManager),
            new MatchingEventsManager($doctrineHelper),
            $this->featureChecker
        );

        $this->calendarEventManager = new CalendarEventManager(
            $updateManager,
            $doctrine,
            $tokenAccessor,
            $entityNameResolver,
            $calendarConfig
        );
    }

    public function testOnEventUpdate()
    {
        $firstEventAttendee = new Attendee(1);
        $firstEventAttendee->setEmail('first@example.com');

        // set default empty data
        $firstEvent = $this->getCalendarEventWithExpectedRelatedAttendee($firstEventAttendee)->setTitle('1');

        $secondEventAttendee = new Attendee(2);
        $secondEventAttendee->setEmail('second@example.com');

        $secondEvent = $this->getCalendarEventWithExpectedRelatedAttendee($secondEventAttendee)->setTitle('2');

        $eventWithoutRelatedAttendee = new CalendarEvent();
        $eventWithoutRelatedAttendee->setTitle('3');

        $parentEventAttendee = new Attendee(3);
        $parentEvent = $this->getCalendarEventWithExpectedRelatedAttendee($parentEventAttendee)
            ->setTitle('parent title')
            ->setDescription('parent description')
            ->setStart(new \DateTime('now'))
            ->setEnd(new \DateTime('now'))
            ->setAllDay(true)
            ->addAttendee($parentEventAttendee)
            ->addChildEvent($firstEvent)
            ->addAttendee($firstEventAttendee)
            ->addChildEvent($secondEvent)
            ->addAttendee($secondEventAttendee)
            ->addChildEvent($eventWithoutRelatedAttendee);

        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_duplications')
            ->willReturn(true);

        // assert default data with default status
        $this->calendarEventManager->onEventUpdate($parentEvent, clone $parentEvent, new Organization(), false);

        $this->assertEquals(Attendee::STATUS_NONE, $parentEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_NONE, $firstEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_NONE, $secondEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_NONE, $eventWithoutRelatedAttendee->getInvitationStatus());
        $this->assertEventDataEquals($parentEvent, $firstEvent);
        $this->assertEventDataEquals($parentEvent, $secondEvent);
        $this->assertEventDataEquals($parentEvent, $eventWithoutRelatedAttendee);

        // modify data
        $parentEvent->setTitle('modified title')
            ->setDescription('modified description')
            ->setStart(new \DateTime('tomorrow'))
            ->setEnd(new \DateTime('tomorrow'))
            ->setAllDay(false);

        $parentEvent->findRelatedAttendee()->setStatus(
            new TestEnumValue(Attendee::STATUS_ACCEPTED, Attendee::STATUS_ACCEPTED)
        );
        $firstEvent->findRelatedAttendee()->setStatus(
            new TestEnumValue(Attendee::STATUS_DECLINED, Attendee::STATUS_DECLINED)
        );
        $secondEvent->findRelatedAttendee()->setStatus(
            new TestEnumValue(Attendee::STATUS_TENTATIVE, Attendee::STATUS_TENTATIVE)
        );

        // assert modified data
        $this->calendarEventManager->onEventUpdate($parentEvent, clone $parentEvent, new Organization(), false);

        $this->assertEquals(Attendee::STATUS_ACCEPTED, $parentEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_DECLINED, $firstEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_TENTATIVE, $secondEvent->getInvitationStatus());
        $this->assertEquals(Attendee::STATUS_NONE, $eventWithoutRelatedAttendee->getInvitationStatus());
        $this->assertEventDataEquals($parentEvent, $firstEvent);
        $this->assertEventDataEquals($parentEvent, $secondEvent);
        $this->assertEventDataEquals($parentEvent, $eventWithoutRelatedAttendee);
    }

    public function testRelatedAttendees()
    {
        $user = new User();

        $calendar = (new Calendar())
            ->setOwner($user);

        $attendees = new ArrayCollection([
            (new Attendee())
                ->setUser($user)
        ]);

        $event = (new CalendarEvent())
            ->setAttendees($attendees)
            ->setCalendar($calendar)
            ->setIsOrganizer(true);

        $this->calendarEventManager->onEventUpdate($event, clone $event, new Organization(), false);

        $this->assertEquals($attendees->first(), $event->findRelatedAttendee());
    }

    public function testAddEvents()
    {
        $user = new User(1);
        $user2 = new User(2);

        $calendar = (new Calendar())
            ->setOwner($user);

        $attendees = new ArrayCollection([
            (new Attendee())
                ->setUser($user),
            (new Attendee())
                ->setUser($user2)
        ]);

        $event = (new CalendarEvent())
            ->setAttendees($attendees)
            ->setCalendar($calendar)
            ->setIsOrganizer(true)
            ->setStart(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setEnd(new \DateTime('now', new \DateTimeZone('UTC')));

        $originalEvent = clone $event;
        $originalEvent->setAttendees(new ArrayCollection());

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_duplications')
            ->willReturn(true);

        $this->calendarEventManager->onEventUpdate($event, $originalEvent, new Organization(), false);

        $this->assertCount(1, $event->getChildEvents());
        $this->assertSame($attendees->get(1), $event->getChildEvents()->first()->findRelatedAttendee());
    }

    public function testAddEventsWithDisabledMasterFeatures()
    {
        $user = new User(1);
        $user2 = new User(2);

        $calendar = (new Calendar())
            ->setOwner($user);

        $attendees = new ArrayCollection([
            (new Attendee())->setUser($user),
            (new Attendee())->setUser($user2)
        ]);

        $event = (new CalendarEvent())
            ->setAttendees($attendees)
            ->setCalendar($calendar)
            ->setIsOrganizer(true)
            ->setStart(new \DateTime('now', new \DateTimeZone('UTC')))
            ->setEnd(new \DateTime('now', new \DateTimeZone('UTC')));

        $originalEvent = clone $event;
        $originalEvent->setAttendees(new ArrayCollection());

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_duplications')
            ->willReturn(false);

        $this->calendarEventManager->onEventUpdate($event, $originalEvent, new Organization(), false);

        $this->assertCount(0, $event->getChildEvents());
    }

    public function testUpdateAttendees()
    {
        $user = (new User())
            ->setFirstName('first')
            ->setLastName('last');

        $calendar = (new Calendar())
            ->setOwner($user);

        $attendees = new ArrayCollection([
            (new Attendee())
                ->setEmail('attendee@example.com')
                ->setUser($user),
            (new Attendee())
                ->setEmail('attendee2@example.com')
        ]);

        $event = (new CalendarEvent())
            ->setAttendees($attendees)
            ->setCalendar($calendar)
            ->setIsOrganizer(true);

        $this->calendarEventManager->onEventUpdate($event, clone $event, new Organization(), false);

        $this->assertEquals('attendee@example.com', $attendees->get(0)->getDisplayName());
        $this->assertEquals('attendee2@example.com', $attendees->get(1)->getDisplayName());
    }

    private function assertEventDataEquals(CalendarEvent $expected, CalendarEvent $actual)
    {
        $this->assertEquals($expected->getTitle(), $actual->getTitle());
        $this->assertEquals($expected->getDescription(), $actual->getDescription());
        $this->assertEquals($expected->getStart(), $actual->getStart());
        $this->assertEquals($expected->getEnd(), $actual->getEnd());
        $this->assertEquals($expected->getAllDay(), $actual->getAllDay());
    }

    private function getCalendarEventWithExpectedRelatedAttendee(Attendee $relatedAttendee): CalendarEvent
    {
        $result = $this->getMockBuilder(CalendarEvent::class)
            ->onlyMethods(['findRelatedAttendee'])
            ->getMock();
        $result->expects($this->any())
            ->method('findRelatedAttendee')
            ->willReturn($relatedAttendee);

        $result->setRelatedAttendee($relatedAttendee);

        return $result;
    }
}
