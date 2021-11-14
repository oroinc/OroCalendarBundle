<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer;
use Oro\Bundle\CalendarBundle\Provider\UserCalendarProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;

class UserCalendarProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var UserCalendarEventNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventNormalizer;

    /** @var Recurrence|\PHPUnit\Framework\MockObject\MockObject */
    private $recurrenceModel;

    /** @var UserCalendarProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->calendarEventNormalizer = $this->createMock(UserCalendarEventNormalizer::class);
        $this->recurrenceModel = $this->createMock(Recurrence::class);

        $this->provider = new UserCalendarProvider(
            $this->doctrineHelper,
            $this->recurrenceModel,
            $this->entityNameResolver,
            $this->calendarEventNormalizer
        );
    }

    public function testGetCalendarDefaultValues()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 20;
        $calendarIds = [10, 20];

        $calendar1 = new Calendar();
        ReflectionUtil::setId($calendar1, $calendarIds[0]);
        $user1 = new User();
        ReflectionUtil::setId($user1, $userId);
        $calendar1->setOwner($user1);

        $calendar2 = new Calendar();
        ReflectionUtil::setId($calendar2, $calendarIds[1]);
        $user2 = new User();
        ReflectionUtil::setId($user2, 456);
        $calendar2->setOwner($user2);

        $calendars = [$calendar1, $calendar2];

        $qb = $this->createMock(QueryBuilder::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:Calendar')
            ->willReturn($repo);
        $qb->expects($this->once())
            ->method('select')
            ->with('o, owner')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('innerJoin')
            ->with('o.owner', 'owner')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn(new Expr());
        $qb->expects($this->once())
            ->method('where')
            ->with(new Expr\Func('o.id IN', [':calendarIds']))
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($calendars);

        $this->entityNameResolver->expects($this->exactly(2))
            ->method('getName')
            ->withConsecutive(
                [$this->identicalTo($user1)],
                [$this->identicalTo($user2)]
            )
            ->willReturnOnConsecutiveCalls(
                'John Doo',
                'John Smith'
            );

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);
        $this->assertEquals(
            [
                $calendarIds[0] => [
                    'calendarName' => 'John Doo',
                    'userId'       => $userId,
                ],
                $calendarIds[1] => [
                    'calendarName'   => 'John Smith',
                    'userId'         => 456,
                    'removable'      => false,
                    'canAddEvent'    => true,
                    'canEditEvent'   => true,
                    'canDeleteEvent' => true,
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetCalendarEvents()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $start = new \DateTime();
        $end = new \DateTime();
        $connections = [10 => true, 20 => false];
        $events = [
            [
                'id'    => 1,
                'start' => '2016-05-04T11:29:46+00:00',
                'end'   => '2016-05-06T11:29:46+00:00',
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => [],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'calculatedEndTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
            ],
        ];
        $expectedEvents = [
            [
                'id'    => 1,
                'start' => '2016-05-04T11:29:46+00:00',
                'end'   => '2016-05-06T11:29:46+00:00',
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => [],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'recurrencePattern' => null,
                'startEditable' => false,
                'durationEditable' => false,
            ],
            [
                'id'    => 1,
                'start' => '2016-05-05T11:29:46+00:00',
                'end'   => '2016-05-07T11:29:46+00:00',
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'instance' => null,
                    'dayOfWeek' => [],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => '2016-05-04T11:29:46+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'recurrencePattern' => null,
                'startEditable' => false,
                'durationEditable' => false,
            ],
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $repo = $this->createMock(CalendarEventRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:CalendarEvent')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('getUserEventListByTimeIntervalQueryBuilder')
            ->with($this->identicalTo($start), $this->identicalTo($end))
            ->willReturn($qb);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('c.id IN (:visibleIds)')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->calendarEventNormalizer->expects($this->once())
            ->method('getCalendarEvents')
            ->with($calendarId, $this->identicalTo($query))
            ->willReturn($events);
        $this->recurrenceModel->expects($this->once())
            ->method('getOccurrences')
            ->willReturn([
                new \DateTime('2016-05-04T11:29:46+00:00'),
                new \DateTime('2016-05-05T11:29:46+00:00'),
            ]);

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals($expectedEvents, $result);
    }

    public function testGetCalendarEventsAllInvisible()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $start = new \DateTime();
        $end = new \DateTime();
        $connections = [10 => false, 20 => false];
        $events = [['id' => 1]];

        $qb = $this->createMock(QueryBuilder::class);
        $repo = $this->createMock(CalendarEventRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:CalendarEvent')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('getUserEventListByTimeIntervalQueryBuilder')
            ->with($this->identicalTo($start), $this->identicalTo($end))
            ->willReturn($qb);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('1 = 0')
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
