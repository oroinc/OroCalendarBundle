<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateAttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateChildManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateExceptionManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures\TestEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Old tests moved after remove of \Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener\ChildEventsSubscriberTest.
 */
class CalendarEventManagerLegacyTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    public function setUp()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['find', 'findDefaultCalendars'])
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('find')
            ->will($this->returnCallback(function ($id) {
                return new TestEnumValue($id, $id);
            }));
        $repository->expects($this->any())
            ->method('findDefaultCalendars')
            ->will($this->returnCallback(function ($userIds) {
                return array_map(
                    function ($userId) {
                        return (new Calendar())
                            ->setOwner(new User($userId));
                    },
                    $userIds
                );
            }));

        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['Extend\Entity\EV_Ce_Attendee_Status', null, $repository],
                ['Extend\Entity\EV_Ce_Attendee_Type', null, $repository],
                ['OroCalendarBundle:Calendar', null, $repository],
            ]));

        $attendeeRelationManager = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValueMap([
                ['Extend\Entity\EV_Ce_Attendee_Status', $repository],
                ['Extend\Entity\EV_Ce_Attendee_Type', $repository],
                ['OroCalendarBundle:Calendar', $repository],
            ]));


        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $calendarConfig = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();

        $attendeeManager = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeManager')
            ->disableOriginalConstructor()
            ->getMock();

        $updateManager = new UpdateManager(
            new UpdateAttendeeManager($attendeeRelationManager, $doctrineHelper),
            new UpdateChildManager($doctrineHelper),
            new UpdateExceptionManager($attendeeManager)
        );

        $this->calendarEventManager = new CalendarEventManager(
            $updateManager,
            $doctrineHelper,
            $securityFacade,
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

        // assert default data with default status
        $this->calendarEventManager->onEventUpdate($parentEvent, clone $parentEvent, new Organization(), false);

        $this->assertEquals(CalendarEvent::STATUS_NONE, $parentEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_NONE, $firstEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_NONE, $secondEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_NONE, $eventWithoutRelatedAttendee->getInvitationStatus());
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
            new TestEnumValue(CalendarEvent::STATUS_ACCEPTED, CalendarEvent::STATUS_ACCEPTED)
        );
        $firstEvent->findRelatedAttendee()->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_DECLINED, CalendarEvent::STATUS_DECLINED)
        );
        $secondEvent->findRelatedAttendee()->setStatus(
            new TestEnumValue(CalendarEvent::STATUS_TENTATIVE, CalendarEvent::STATUS_TENTATIVE)
        );

        // assert modified data
        $this->calendarEventManager->onEventUpdate($parentEvent, clone $parentEvent, new Organization(), false);

        $this->assertEquals(CalendarEvent::STATUS_ACCEPTED, $parentEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_DECLINED, $firstEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_TENTATIVE, $secondEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::STATUS_NONE, $eventWithoutRelatedAttendee->getInvitationStatus());
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
            ->setCalendar($calendar);

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
            ->setCalendar($calendar);

        $originalEvent = clone $event;
        $originalEvent->setAttendees(new ArrayCollection());
        $this->calendarEventManager->onEventUpdate($event, $originalEvent, new Organization(), false);

        $this->assertCount(1, $event->getChildEvents());
        $this->assertSame($attendees->get(1), $event->getChildEvents()->first()->findRelatedAttendee());
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
            ->setCalendar($calendar);

        $this->calendarEventManager->onEventUpdate($event, clone $event, new Organization(), false);

        $this->assertEquals('attendee@example.com', $attendees->get(0)->getDisplayName());
        $this->assertEquals('attendee2@example.com', $attendees->get(1)->getDisplayName());
    }

    /**
     * @param CalendarEvent $expected
     * @param CalendarEvent $actual
     */
    protected function assertEventDataEquals(CalendarEvent $expected, CalendarEvent $actual)
    {
        $this->assertEquals($expected->getTitle(), $actual->getTitle());
        $this->assertEquals($expected->getDescription(), $actual->getDescription());
        $this->assertEquals($expected->getStart(), $actual->getStart());
        $this->assertEquals($expected->getEnd(), $actual->getEnd());
        $this->assertEquals($expected->getAllDay(), $actual->getAllDay());
    }


    /**
     * @param Attendee $relatedAttendee
     * @return CalendarEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCalendarEventWithExpectedRelatedAttendee(Attendee $relatedAttendee)
    {
        $result = $this->getMockBuilder(CalendarEvent::class)
            ->setMethods(['findRelatedAttendee'])
            ->getMock();

        $result->expects($this->any())
            ->method('findRelatedAttendee')
            ->will($this->returnValue($relatedAttendee));

        $result->setRelatedAttendee($relatedAttendee);

        return $result;
    }
}
