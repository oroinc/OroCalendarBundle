<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Exception\ChangeInvitationStatusException;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpdateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $updateManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var SystemCalendarConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarConfig;

    /** @var CalendarEventManager */
    private $manager;

    protected function setUp(): void
    {
        $this->updateManager = $this->createMock(UpdateManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->calendarConfig = $this->createMock(SystemCalendarConfig::class);

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
        $calendars = [
            ['id' => 123, 'name' => 'test', 'public' => true]
        ];

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn($organizationId);

        $repo = $this->createMock(SystemCalendarRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('getCalendarsQueryBuilder')
            ->with($organizationId)
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('sc.id, sc.name, sc.public')
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($calendars);

        $result = $this->manager->getSystemCalendars();
        $this->assertEquals($calendars, $result);
    }

    public function testGetUserCalendars()
    {
        $organizationId = 1;
        $userId = 10;
        $user = new User();
        $calendars = [
            ['id' => 100, 'name' => null],
            ['id' => 200, 'name' => 'name2'],
        ];

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn($organizationId);
        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $repo = $this->createMock(CalendarRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('getUserCalendarsQueryBuilder')
            ->with($organizationId)
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('c.id, c.name')
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($calendars);

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($user))
            ->willReturn('name1');

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
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $repo = $this->createMock(CalendarRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->willReturn($calendar);

        $this->manager->setCalendar($event, Calendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getCalendar());
    }

    public function testSetSameUserCalendar()
    {
        $calendarId = 123;
        $calendar = new Calendar();
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
        $calendar = new SystemCalendar();
        $calendar->setPublic(false);
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);
        $repo = $this->createMock(SystemCalendarRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->willReturn($calendar);

        $this->manager->setCalendar($event, SystemCalendar::CALENDAR_ALIAS, $calendarId);

        $this->assertSame($calendar, $event->getSystemCalendar());
    }

    public function testSetPublicCalendar()
    {
        $calendarId = 123;
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        ReflectionUtil::setId($calendar, $calendarId);

        $event = new CalendarEvent();

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $repo = $this->createMock(SystemCalendarRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($calendarId)
            ->willReturn($calendar);

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

        $statusRepository = $this->createMock(ObjectRepository::class);
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(Attendee::STATUS_ACCEPTED)
            ->willReturn($status);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->willReturn($statusRepository);

        $attendee = new Attendee();
        $attendee->setUser($user);

        $event = $this->getCalendarEventWithExpectedRelatedAttendee($attendee);

        $this->assertNotEquals(Attendee::STATUS_ACCEPTED, $event->getInvitationStatus());

        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
        $this->assertEquals(Attendee::STATUS_ACCEPTED, $event->getInvitationStatus());
    }

    public function testChangeInvitationStatusWithEmptyRelatedAttendee()
    {
        $this->expectException(ChangeInvitationStatusException::class);
        $this->expectExceptionMessage('Cannot change invitation status of the event with no related attendee.');

        $user = new User();
        $user->setId(100);
        $event = new CalendarEvent();
        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
    }

    public function testChangeInvitationStatusWithNonExistingStatus()
    {
        $this->expectException(ChangeInvitationStatusException::class);
        $this->expectExceptionMessage('Status "accepted" does not exists');

        $user = new User();
        $user->setId(100);

        $statusRepository = $this->createMock(ObjectRepository::class);
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(Attendee::STATUS_ACCEPTED)
            ->willReturn(null);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->willReturn($statusRepository);

        $attendee = new Attendee();
        $attendee->setUser($user);

        $event = $this->getCalendarEventWithExpectedRelatedAttendee($attendee);

        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
    }

    public function testChangeInvitationStatusWithDifferentRelatedAttendeeUser()
    {
        $this->expectException(ChangeInvitationStatusException::class);
        $this->expectExceptionMessage('Cannot change invitation status of the event.');

        $user = new User();
        $user->setId(100);

        $status = new TestEnumValue(Attendee::STATUS_ACCEPTED, Attendee::STATUS_ACCEPTED);

        $statusRepository = $this->createMock(ObjectRepository::class);
        $statusRepository->expects($this->any())
            ->method('find')
            ->with(Attendee::STATUS_ACCEPTED)
            ->willReturn($status);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->willReturn($statusRepository);

        $attendee = new Attendee();
        $attendee->setUser(new User());

        $event = $this->getCalendarEventWithExpectedRelatedAttendee($attendee);

        $this->manager->changeInvitationStatus($event, Attendee::STATUS_ACCEPTED, $user);
    }

    private function getCalendarEventWithExpectedRelatedAttendee(Attendee $relatedAttendee): CalendarEvent
    {
        $result = $this->getMockBuilder(CalendarEvent::class)
            ->onlyMethods(['getRelatedAttendee'])
            ->getMock();
        $result->expects($this->any())
            ->method('getRelatedAttendee')
            ->willReturn($relatedAttendee);

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
