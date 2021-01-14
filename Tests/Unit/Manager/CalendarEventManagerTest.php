<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $updateManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarConfig;

    /** @var CalendarEventManager */
    protected $manager;

    protected function setUp(): void
    {
        $this->updateManager      = $this->getMockBuilder(
            'Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateManager'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine           = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarConfig     =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();

        $this->manager = new CalendarEventManager(
            $this->updateManager,
            $this->doctrine,
            $this->tokenAccessor,
            $this->entityNameResolver,
            $this->calendarConfig
        );
    }

    public function testGetSystemCalendars()
    {
        $organizationId = 1;
        $calendars      = [
            ['id' => 123, 'name' => 'test', 'public' => true]
        ];

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getCalendarsQueryBuilder')
            ->with($organizationId)
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('select')
            ->with('sc.id, sc.name, sc.public')
            ->will($this->returnSelf());
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($calendars));

        $result = $this->manager->getSystemCalendars();
        $this->assertEquals($calendars, $result);
    }

    public function testGetUserCalendars()
    {
        $organizationId = 1;
        $userId         = 10;
        $user           = new User();
        $calendars      = [
            ['id' => 100, 'name' => null],
            ['id' => 200, 'name' => 'name2'],
        ];

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));
        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->will($this->returnValue($userId));
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getUserCalendarsQueryBuilder')
            ->with($organizationId)
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('select')
            ->with('c.id, c.name')
            ->will($this->returnSelf());
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($calendars));

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($user))
            ->will($this->returnValue('name1'));

        $result = $this->manager->getUserCalendars();
        $this->assertEquals(
            [
                ['id' => 100, 'name' => 'name1'],
                ['id' => 200, 'name' => 'name2'],
            ],
            $result
        );
    }

    public function testSetCalendarUnknownAlias()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected calendar alias: "unknown". CalendarId: 123.');

        $event = new CalendarEvent();

        $this->manager->setCalendar($event, 'unknown', 123);
    }

    public function testSetUserCalendar()
    {
        $calendarId = 123;
        $calendar   = new Calendar();
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->will($this->returnValue($calendar));

        $this->manager->setCalendar($event, Calendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getCalendar());
    }

    public function testSetSameUserCalendar()
    {
        $calendarId = 123;
        $calendar   = new Calendar();
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();
        $event->setCalendar($calendar);

        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $this->manager->setCalendar($event, Calendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getCalendar());
    }

    public function testSetSystemCalendar()
    {
        $calendarId = 123;
        $calendar   = new SystemCalendar();
        $calendar->setPublic(false);
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->will($this->returnValue($calendar));

        $this->manager->setCalendar($event, SystemCalendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getSystemCalendar());
    }

    public function testSetPublicCalendar()
    {
        $calendarId = 123;
        $calendar   = new SystemCalendar();
        $calendar->setPublic(true);
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->will($this->returnValue($calendar));

        $this->manager->setCalendar($event, SystemCalendar::PUBLIC_CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getSystemCalendar());
    }

    public function testGetCalendarUid()
    {
        $this->assertEquals('test_123', $this->manager->getCalendarUid('test', 123));
    }

    public function testParseCalendarUid()
    {
        [$alias, $id] = $this->manager->parseCalendarUid('some_alias_123');
        $this->assertSame('some_alias', $alias);
        $this->assertSame(123, $id);
    }

    public function testChangeInvitationStatus()
    {
        $user = new User();
        $user->setId(100);

        $status = new TestEnumValue(Attendee::STATUS_ACCEPTED, Attendee::STATUS_ACCEPTED);

        $statusRepository = $this->createMock('Doctrine\Persistence\ObjectRepository');
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(Attendee::STATUS_ACCEPTED)
            ->will($this->returnValue($status));

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->will($this->returnValue($statusRepository));

        $attendee = new Attendee();
        $attendee->setUser($user);

        $event = $this->getCalendarEventWithExpectedRelatedAttendee($attendee);

        $this->assertNotEquals(Attendee::STATUS_ACCEPTED, $event->getInvitationStatus());

        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
        $this->assertEquals(Attendee::STATUS_ACCEPTED, $event->getInvitationStatus());
    }

    public function testChangeInvitationStatusWithEmptyRelatedAttendee()
    {
        $this->expectException(\Oro\Bundle\CalendarBundle\Exception\ChangeInvitationStatusException::class);
        $this->expectExceptionMessage('Cannot change invitation status of the event with no related attendee.');

        $user = new User();
        $user->setId(100);
        $event = new CalendarEvent();
        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
    }

    public function testChangeInvitationStatusWithNonExistingStatus()
    {
        $this->expectException(\Oro\Bundle\CalendarBundle\Exception\ChangeInvitationStatusException::class);
        $this->expectExceptionMessage('Status "accepted" does not exists');

        $user = new User();
        $user->setId(100);

        $statusRepository = $this->createMock('Doctrine\Persistence\ObjectRepository');
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(Attendee::STATUS_ACCEPTED)
            ->will($this->returnValue(null));

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->will($this->returnValue($statusRepository));

        $attendee = new Attendee();
        $attendee->setUser($user);

        $event = $this->getCalendarEventWithExpectedRelatedAttendee($attendee);

        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
    }

    public function testChangeInvitationStatusWithDifferentRelatedAttendeeUser()
    {
        $this->expectException(\Oro\Bundle\CalendarBundle\Exception\ChangeInvitationStatusException::class);
        $this->expectExceptionMessage('Cannot change invitation status of the event.');

        $user = new User();
        $user->setId(100);

        $status = new TestEnumValue(Attendee::STATUS_ACCEPTED, Attendee::STATUS_ACCEPTED);

        $statusRepository = $this->createMock('Doctrine\Persistence\ObjectRepository');
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(Attendee::STATUS_ACCEPTED)
            ->will($this->returnValue($status));

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->will($this->returnValue($statusRepository));

        $attendee = new Attendee();
        $attendee->setUser(new User());

        $event = $this->getCalendarEventWithExpectedRelatedAttendee($attendee);

        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
    }

    /**
     * @param Attendee $relatedAttendee
     * @return CalendarEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getCalendarEventWithExpectedRelatedAttendee(Attendee $relatedAttendee)
    {
        $result = $this->getMockBuilder(CalendarEvent::class)
            ->setMethods(['getRelatedAttendee'])
            ->getMock();

        $result->expects($this->any())
            ->method('getRelatedAttendee')
            ->will($this->returnValue($relatedAttendee));

        $result->addAttendee($relatedAttendee);

        return $result;
    }

    public function testOnEventUpdate()
    {
        $entity = new CalendarEvent();
        $entity->setTitle('New Title');

        $originalEntity = clone $entity;
        $originalEntity->setTitle('Original Title test');

        $organization = new Organization();

        $allowUpdateExceptions = true;

        $this->updateManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($entity, $originalEntity, $organization, $allowUpdateExceptions);

        $this->manager->onEventUpdate($entity, $originalEntity, $organization, $allowUpdateExceptions);
    }
}
