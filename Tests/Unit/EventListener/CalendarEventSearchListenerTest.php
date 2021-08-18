<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\EventListener\CalendarEventSearchListener;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarEventSearchListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CalendarEventSearchListener */
    private $listener;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new CalendarEventSearchListener($this->urlGenerator, $this->doctrine);
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    private function getSystemCalendar(int $id): SystemCalendar
    {
        $systemCalendar = new SystemCalendar();
        ReflectionUtil::setId($systemCalendar, $id);

        return $systemCalendar;
    }

    public function testPrepareEntityMapEventWithNonCalendarEventEntity()
    {
        $event = new PrepareEntityMapEvent(new \stdClass(), \stdClass::class, [], []);

        $this->listener->prepareEntityMapEvent($event);

        self::assertEquals([], $event->getData());
    }

    public function testPrepareEntityMapEventWithCommonCalendarEventEntity()
    {
        $calendar = new Calendar();
        $calendar->setOrganization($this->getOrganization(12));
        $entity = new CalendarEvent();
        $entity->setCalendar($calendar);

        $event = new PrepareEntityMapEvent($entity, CalendarEvent::class, [], []);

        $this->listener->prepareEntityMapEvent($event);

        self::assertEquals(
            ['integer' => ['organization' => 12]],
            $event->getData()
        );
    }

    public function testPrepareEntityMapEventWithSystemCalendarEventEntity()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        $entity = new CalendarEvent();
        $entity->setSystemCalendar($calendar);

        $event = new PrepareEntityMapEvent($entity, CalendarEvent::class, [], []);

        $this->listener->prepareEntityMapEvent($event);

        self::assertEquals(
            ['integer' => ['organization' => 0]],
            $event->getData()
        );
    }

    public function testPrepareEntityMapEventWithOrganizationCalendarEventEntity()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(false);
        $calendar->setOrganization($this->getOrganization(10));
        $entity = new CalendarEvent();
        $entity->setSystemCalendar($calendar);

        $event = new PrepareEntityMapEvent($entity, CalendarEvent::class, [], []);

        $this->listener->prepareEntityMapEvent($event);

        self::assertEquals(
            ['integer' => ['organization' => 10]],
            $event->getData()
        );
    }

    public function testPrepareResultItemEventWithNonCalendarEventEntity()
    {
        $item = new Item(\stdClass::class, 1);
        $event = new PrepareResultItemEvent($item);

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->prepareResultItemEvent($event);
        self::assertNull($item->getRecordUrl());
    }

    public function testPrepareResultItemEventWithNonSystemCalendarEventEntity()
    {
        $entity = new CalendarEvent();
        $item = new Item(CalendarEvent::class, 1);
        $event = new PrepareResultItemEvent($item, $entity);

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->prepareResultItemEvent($event);
        self::assertNull($item->getRecordUrl());
    }

    public function testPrepareResultItemEventWithSystemCalendarEventEntity()
    {
        $entity = new CalendarEvent();
        $entity->setSystemCalendar($this->getSystemCalendar(10));
        $item = new Item(CalendarEvent::class, 5);
        $event = new PrepareResultItemEvent($item, $entity);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'oro_system_calendar_event_view',
                ['id' => 5],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('http://test.com/calendar/5');

        $this->listener->prepareResultItemEvent($event);
        self::assertEquals('http://test.com/calendar/5', $item->getRecordUrl());
    }

    public function testPrepareResultItemEventWithSystemCalendarEventEntityNotInEvent()
    {
        $entity = new CalendarEvent();
        $entity->setSystemCalendar($this->getSystemCalendar(20));
        $item = new Item(CalendarEvent::class, 10);
        $event = new PrepareResultItemEvent($item);

        $em = $this->createMock(ObjectManager::class);
        $repo = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(CalendarEvent::class)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('getRepository')
            ->with(CalendarEvent::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('find')
            ->with(10)
            ->willReturn($entity);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'oro_system_calendar_event_view',
                ['id' => 10],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn('http://test.com/calendar/10');

        $this->listener->prepareResultItemEvent($event);
        self::assertEquals('http://test.com/calendar/10', $item->getRecordUrl());
    }
}
