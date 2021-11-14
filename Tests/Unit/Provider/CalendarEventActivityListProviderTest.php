<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\CalendarEventActivityListProvider;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventActivityListProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ActivityAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $activityAssociationHelper;

    /** @var CommentAssociationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $commentAssociationHelper;

    /** @var CalendarEventActivityListProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->activityAssociationHelper = $this->createMock(ActivityAssociationHelper::class);
        $this->commentAssociationHelper = $this->createMock(CommentAssociationHelper::class);

        $this->provider = new CalendarEventActivityListProvider(
            $this->doctrineHelper,
            $this->activityAssociationHelper,
            $this->commentAssociationHelper
        );
    }

    public function testIsApplicableTarget()
    {
        $entityClass = \stdClass::class;
        $accessible = false;

        $this->activityAssociationHelper->expects($this->once())
            ->method('isActivityAssociationEnabled')
            ->with($entityClass, CalendarEvent::class, $accessible)
            ->willReturn(true);

        $this->assertTrue($this->provider->isApplicableTarget($entityClass, $accessible));
    }

    /**
     * @dataProvider getRoutesDataProvider
     */
    public function testGetRoutes(CalendarEvent $calendarEvent, array $expected)
    {
        $this->assertEquals($expected, $this->provider->getRoutes($calendarEvent));
    }

    public function getRoutesDataProvider(): array
    {
        return [
            'for calendar event' => [
                'calendarEvent' => $this->getEntity(CalendarEvent::class, ['calendar' => new Calendar()]),
                'expected' => [
                    'itemViewLink' => 'oro_calendar_event_view',
                    'itemView' => 'oro_calendar_event_widget_info',
                    'itemEdit' => 'oro_calendar_event_update',
                    'itemDelete' => 'oro_calendar_event_delete'
                ]
            ],
            'for system calendar event' => [
                'calendarEvent' => $this->getEntity(CalendarEvent::class, ['systemCalendar' => new SystemCalendar()]),
                'expected' => [
                    'itemViewLink' => 'oro_system_calendar_event_view',
                    'itemView' => 'oro_system_calendar_event_widget_info',
                    'itemEdit' => 'oro_system_calendar_event_update',
                    'itemDelete' => 'oro_calendar_event_delete'
                ]
            ],
        ];
    }

    public function testGetSubject()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setTitle('test title');

        $this->assertSame($calendarEvent->getTitle(), $this->provider->getSubject($calendarEvent));
    }

    public function testGetDescription()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setDescription(' <p>test description</p>   ');

        $this->assertSame('test description', $this->provider->getDescription($calendarEvent));
    }

    public function testGetCreatedAt()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setCreatedAt(new \DateTime('now'));

        $this->assertSame($calendarEvent->getCreatedAt(), $this->provider->getCreatedAt($calendarEvent));
    }

    public function testGetUpdatedAt()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUpdatedAt(new \DateTime('now'));

        $this->assertSame($calendarEvent->getUpdatedAt(), $this->provider->getUpdatedAt($calendarEvent));
    }

    public function testGetData()
    {
        $this->assertSame([], $this->provider->getData(new ActivityList()));
    }

    /**
     * @dataProvider getOrganizationDataProvider
     */
    public function testGetOrganization(CalendarEvent $calendarEvent, Organization $expected = null)
    {
        $this->assertSame($expected, $this->provider->getOrganization($calendarEvent));
    }

    public function getOrganizationDataProvider(): array
    {
        $organization = new Organization();

        return [
            'for calendar event' => [
                'calendarEvent' => $this->getEntity(
                    CalendarEvent::class,
                    [
                        'calendar' => $this->getEntity(Calendar::class, ['organization' => $organization])
                    ]
                ),
                'expected' => $organization
            ],
            'for system calendar event' => [
                'calendarEvent' => $this->getEntity(
                    CalendarEvent::class,
                    [
                        'systemCalendar' => $this->getEntity(SystemCalendar::class, ['organization' => $organization])
                    ]
                ),
                'expected' => $organization
            ],
            'empty calendar event' => [
                'calendarEvent' => new CalendarEvent(),
                'expected' => null
            ],
        ];
    }

    public function testGetTemplate()
    {
        $this->assertSame(
            '@OroCalendar/CalendarEvent/js/activityItemTemplate.html.twig',
            $this->provider->getTemplate()
        );
    }

    public function testGetActivityId()
    {
        $calendarEvent = new CalendarEvent();
        $id = 42;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($calendarEvent)
            ->willReturn($id);

        $this->assertSame($id, $this->provider->getActivityId($calendarEvent));
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(object|string $entity, bool $expected)
    {
        $this->assertEquals($expected, $this->provider->isApplicable($entity));
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'not applicable entity' => [
                'entity' => new \stdClass(),
                'expected' => false
            ],
            'not applicable CalendarEvent entity' => [
                'entity' => $this->getEntity(CalendarEvent::class, ['recurringEvent' => new CalendarEvent()]),
                'expected' => false
            ],
            'applicable CalendarEvent entity' => [
                'entity' => new CalendarEvent(),
                'expected' => true
            ],
            'not applicable entity class' => [
                'entity' => \stdClass::class,
                'expected' => false
            ],
            'applicable entity class' => [
                'entity' => CalendarEvent::class,
                'expected' => true
            ],
        ];
    }

    public function testGetTargetEntities()
    {
        $targetEntities = [new CalendarEvent()];

        $activity = $this->createMock(ActivityInterface::class);
        $activity->expects($this->once())
            ->method('getActivityTargets')
            ->willReturn($targetEntities);

        $this->assertSame($targetEntities, $this->provider->getTargetEntities($activity));
    }

    public function testIsCommentsEnabled()
    {
        $this->commentAssociationHelper->expects($this->once())
            ->method('isCommentAssociationEnabled')
            ->with(CalendarEvent::class)
            ->willReturn(true);

        $this->assertTrue($this->provider->isCommentsEnabled(CalendarEvent::class));
    }

    /**
     * @dataProvider getActivityOwnersDataProvider
     */
    public function testGetActivityOwners(CalendarEvent $entity, ActivityList $activity, array $expected)
    {
        $this->assertEquals($expected, $this->provider->getActivityOwners($entity, $activity));
    }

    public function getActivityOwnersDataProvider(): array
    {
        $organization = new Organization();
        $user = new User();
        $activityList = new ActivityList();

        return [
            'with owner and organization' => [
                'entity' => $this->getEntity(
                    CalendarEvent::class,
                    [
                        'calendar' => $this->getEntity(
                            Calendar::class,
                            [
                                'organization' => $organization,
                                'owner' => $user
                            ]
                        )
                    ]
                ),
                $activityList,
                'expected' => [
                    $this->getEntity(
                        ActivityOwner::class,
                        [
                            'activity' => $activityList,
                            'organization' => $organization,
                            'user' => $user,
                        ]
                    )
                ]
            ],
            'without owner' => [
                'entity' => $this->getEntity(
                    CalendarEvent::class,
                    [
                        'calendar' => $this->getEntity(
                            Calendar::class,
                            [
                                'organization' => $organization,
                            ]
                        )
                    ]
                ),
                $activityList,
                'expected' => []
            ],
            'without organization' => [
                'entity' => $this->getEntity(
                    CalendarEvent::class,
                    [
                        'calendar' => $this->getEntity(
                            Calendar::class,
                            [
                                'owner' => $user
                            ]
                        )
                    ]
                ),
                $activityList,
                'expected' => []
            ]
        ];
    }

    /**
     * @dataProvider getOwnerDataProvider
     */
    public function testGetOwner(CalendarEvent $calendarEvent, ?User $user)
    {
        $this->assertSame($user, $this->provider->getOwner($calendarEvent));
    }

    public function getOwnerDataProvider(): array
    {
        $user = new User();

        return [
            'with calendar' => [
                'calendarEvent' => $this->getEntity(
                    CalendarEvent::class,
                    [
                        'calendar' => $this->getEntity(Calendar::class, ['owner' => $user])
                    ]
                ),
                'expected' => $user
            ],
            'empty calendar event' => [
                'calendarEvent' => new CalendarEvent(),
                'expected' => null
            ],
        ];
    }
}
