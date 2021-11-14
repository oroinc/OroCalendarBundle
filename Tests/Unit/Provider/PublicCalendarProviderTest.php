<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Provider\PublicCalendarEventNormalizer;
use Oro\Bundle\CalendarBundle\Provider\PublicCalendarProvider;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PublicCalendarProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Recurrence|\PHPUnit\Framework\MockObject\MockObject */
    private $recurrenceModel;

    /** @var PublicCalendarEventNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventNormalizer;

    /** @var SystemCalendarConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarConfig;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var PublicCalendarProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->recurrenceModel = $this->createMock(Recurrence::class);
        $this->calendarEventNormalizer = $this->createMock(PublicCalendarEventNormalizer::class);
        $this->calendarConfig = $this->createMock(SystemCalendarConfig::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->provider = new PublicCalendarProvider(
            $this->doctrineHelper,
            $this->recurrenceModel,
            $this->calendarEventNormalizer,
            $this->calendarConfig,
            $this->authorizationChecker
        );
    }

    public function testGetCalendarDefaultValuesDisabled()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $calendarIds = [10];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(false);

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                10 => null
            ],
            $result
        );
    }

    public function testGetCalendarDefaultValuesCannotAddEvents()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $calendarIds = [10];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $calendar1->setName('Master');
        $calendar1->setBackgroundColor('#FF0000');

        $calendars = [$calendar1];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);

        $repo = $this->createMock(SystemCalendarRepository::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('getPublicCalendarsQueryBuilder')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($calendars);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->willReturn($repo);

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                $calendar1->getId() => [
                    'calendarName'    => $calendar1->getName(),
                    'backgroundColor' => $calendar1->getBackgroundColor(),
                    'removable'       => false,
                    'position'        => -80,
                ]
            ],
            $result
        );
    }

    public function testGetCalendarDefaultValuesCanAddEvents()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $calendarIds = [10];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $calendar1->setName('Master');
        $calendar1->setBackgroundColor('#FF0000');

        $calendars = [$calendar1];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->willReturn(true);

        $repo = $this->createMock(SystemCalendarRepository::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('getPublicCalendarsQueryBuilder')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($calendars);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->willReturn($repo);

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                $calendar1->getId() => [
                    'calendarName'    => $calendar1->getName(),
                    'backgroundColor' => $calendar1->getBackgroundColor(),
                    'removable'       => false,
                    'position'        => -80,
                    'canAddEvent'     => true,
                    'canEditEvent'    => true,
                    'canDeleteEvent'  => true,
                ]
            ],
            $result
        );
    }

    public function testGetCalendarEventsPublicNotSupport()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $start = new \DateTime();
        $end = new \DateTime();
        $connections = [10 => true, 20 => false];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(false);

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals([], $result);
    }

    public function testGetCalendarEvents()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $start = new \DateTime();
        $end = new \DateTime();
        $connections = [10 => true, 20 => false];
        $events = [['id' => 1]];

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $qb = $this->createMock(QueryBuilder::class);
        $repo = $this->createMock(CalendarEventRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:CalendarEvent')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('getPublicEventListByTimeIntervalQueryBuilder')
            ->with($this->identicalTo($start), $this->identicalTo($end))
            ->willReturn($qb);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('c.id NOT IN (:invisibleIds)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('invisibleIds', [20])
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->calendarEventNormalizer->expects($this->once())
            ->method('getCalendarEvents')
            ->with($calendarId, $this->identicalTo($query))
            ->willReturn($events);

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals($events, $result);
    }
}
