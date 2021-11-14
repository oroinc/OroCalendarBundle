<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee as AttendeeStub;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AttendeeManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarEventRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventRepository;

    /** @var AttendeeRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $attendeeRepository;

    /** @var AttendeeRelationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attendeeRelationManager;

    /** @var AttendeeManager */
    private $attendeeManager;

    protected function setUp(): void
    {
        $this->calendarEventRepository = $this->createMock(CalendarEventRepository::class);
        $this->attendeeRepository = $this->createMock(AttendeeRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entity) {
                return $entity->getId();
            });
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap([
                ['OroCalendarBundle:CalendarEvent', $this->calendarEventRepository],
                ['OroCalendarBundle:Attendee', $this->attendeeRepository],
            ]);

        $this->attendeeRelationManager = $this->createMock(AttendeeRelationManager::class);
        $this->attendeeRelationManager->expects($this->any())
            ->method('getRelatedEntity')
            ->willReturnCallback(function ($attendee) {
                return $attendee->getUser();
            });

        $this->attendeeManager = new AttendeeManager(
            $doctrineHelper,
            $this->attendeeRelationManager
        );
    }

    public function testLoadAttendeesByCalendarEventId()
    {
        $this->attendeeRepository->expects($this->once())
            ->method('findBy')
            ->with(['calendarEvent' => 1])
            ->willReturn(new AttendeeStub(1));

        $this->attendeeManager->loadAttendeesByCalendarEventId(1);
    }

    /**
     * @dataProvider createAttendeeExclusionsProvider
     */
    public function testCreateAttendeeExclusions(array|ArrayCollection $attendees, array $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            $this->attendeeManager->createAttendeeExclusions($attendees)
        );
    }

    public function createAttendeeExclusionsProvider(): array
    {
        $attendees = [
            (new AttendeeStub(1))
                ->setUser(new User(3)),
            new AttendeeStub(2)
        ];

        $key = json_encode([
            'entityClass' => User::class,
            'entityId' => 3,
        ], JSON_THROW_ON_ERROR);
        $val = json_encode([
            'entityClass' => Attendee::class,
            'entityId' => 1
        ], JSON_THROW_ON_ERROR);

        return [
            'no attendees' => [
                [],
                [],
            ],
            'array of attendees' => [
                $attendees,
                [$key => $val],
            ],
            'collection of attendees' => [
                new ArrayCollection($attendees),
                [$key => $val],
            ],
        ];
    }

    /**
     * @dataProvider getAttendeeListsByCalendarEventIdsDataProvider
     */
    public function testGetAttendeeListsByCalendarEventIds(
        array $calendarEventIds,
        array $parentToChildren,
        array $queryResult,
        array $expectedResult
    ) {
        $query = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($queryResult);

        $this->calendarEventRepository->expects($this->once())
            ->method('getParentEventIds')
            ->with($calendarEventIds)
            ->willReturn($parentToChildren);

        $this->attendeeRepository->expects($this->once())
            ->method('createAttendeeListsQb')
            ->with(array_keys($parentToChildren))
            ->willReturn($qb);

        $this->attendeeRelationManager->expects($this->once())
            ->method('addRelatedEntityInfo')
            ->with($qb);

        $result = $this->attendeeManager->getAttendeeListsByCalendarEventIds($calendarEventIds);
        $this->assertEquals($expectedResult, $result);
    }

    public function getAttendeeListsByCalendarEventIdsDataProvider(): array
    {
        return [
            [
                [1, 2, 3],
                [
                    1 => [1],
                    4 => [2, 3],
                ],
                [
                    [
                        'calendarEventId' => 1,
                        'email' => 'first@example.com',
                    ],
                    [
                        'calendarEventId' => 4,
                        'email' => 'fourth@example.com',
                    ]
                ],
                [
                    1 => [
                        ['email' => 'first@example.com'],
                    ],
                    2 => [
                        ['email' => 'fourth@example.com'],
                    ],
                    3 => [
                        ['email' => 'fourth@example.com'],
                    ],
                ],
            ],
        ];
    }
}
