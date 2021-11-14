<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Manager\CalendarManager;
use Oro\Bundle\CalendarBundle\Provider\CalendarPropertyProvider;
use Oro\Bundle\CalendarBundle\Provider\CalendarProviderInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class CalendarManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarPropertyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarPropertyProvider;

    /** @var CalendarProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var CalendarProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var CalendarManager */
    private $manager;

    protected function setUp(): void
    {
        $this->calendarPropertyProvider =$this->createMock(CalendarPropertyProvider::class);
        $this->provider1 = $this->createMock(CalendarProviderInterface::class);
        $this->provider2 = $this->createMock(CalendarProviderInterface::class);

        $providerContainer = TestContainerBuilder::create()
            ->add('provider1', $this->provider1)
            ->add('provider2', $this->provider2)
            ->getContainer($this);

        $this->manager = new CalendarManager(
            ['provider1', 'provider2'],
            $providerContainer,
            $this->calendarPropertyProvider
        );
    }

    public function testGetCalendarsEmpty()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 2;
        $connections = [];

        $this->calendarPropertyProvider->expects($this->once())
            ->method('getItems')
            ->with($calendarId)
            ->willReturn($connections);

        $this->provider1->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [])
            ->willReturn([]);
        $this->provider2->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [])
            ->willReturn([]);

        $result = $this->manager->getCalendars($organizationId, $userId, $calendarId);
        $this->assertSame([], $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetCalendars()
    {
        $organizationId = 123;
        $userId = 123;
        $calendarId = 2;
        $connections = [
            [
                'id'             => 1,
                'targetCalendar' => $calendarId,
                'calendarAlias'  => 'provider1',
                'calendar'       => 1,
                'visible'        => true,
                'position'       => 2,
                'extra_field'    => null,
            ],
            [
                'id'             => 2,
                'targetCalendar' => $calendarId,
                'calendarAlias'  => 'provider1',
                'calendar'       => 10,
                'visible'        => true,
                'position'       => 3,
                'extra_field'    => null,
            ],
            [
                'id'             => 2,
                'targetCalendar' => $calendarId,
                'calendarAlias'  => 'provider2',
                'calendar'       => 2,
                'visible'        => false,
                'position'       => 1,
                'extra_field'    => 'opt2',
            ],
        ];
        $defaultValues = [
            'id'             => null,
            'targetCalendar' => null,
            'calendarAlias'  => null,
            'calendar'       => null,
            'visible'        => true,
            'position'       => 0,
            'extra_field'    => [$this, 'getExtraFieldDefaultValue'],
        ];

        $this->calendarPropertyProvider->expects($this->once())
            ->method('getItems')
            ->with($calendarId)
            ->willReturn($connections);
        $this->calendarPropertyProvider->expects($this->once())
            ->method('getDefaultValues')
            ->willReturn($defaultValues);

        $this->provider1->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [1, 10])
            ->willReturn(
                [
                    1  => [
                        'calendarName' => 'calendar1'
                    ],
                    10 => null
                ]
            );
        $this->provider2->expects($this->once())
            ->method('getCalendarDefaultValues')
            ->with($organizationId, $userId, $calendarId, [2])
            ->willReturn(
                [
                    2 => [
                        'calendarName' => 'calendar2'
                    ],
                    3 => [
                        'calendarName' => 'calendar3'
                    ],
                ]
            );

        $result = $this->manager->getCalendars($organizationId, $userId, $calendarId);
        $this->assertEquals(
            [
                [
                    'id'             => null,
                    'targetCalendar' => $calendarId,
                    'calendarAlias'  => 'provider2',
                    'calendar'       => 3,
                    'visible'        => true,
                    'position'       => 0,
                    'extra_field'    => 'def_opt',
                    'calendarName'   => 'calendar3',
                    'removable'      => true
                ],
                [
                    'id'             => 2,
                    'targetCalendar' => $calendarId,
                    'calendarAlias'  => 'provider2',
                    'calendar'       => 2,
                    'visible'        => false,
                    'position'       => 1,
                    'extra_field'    => 'opt2',
                    'calendarName'   => 'calendar2',
                    'removable'      => true
                ],
                [
                    'id'             => 1,
                    'targetCalendar' => $calendarId,
                    'calendarAlias'  => 'provider1',
                    'calendar'       => 1,
                    'visible'        => true,
                    'position'       => 2,
                    'extra_field'    => 'def_opt',
                    'calendarName'   => 'calendar1',
                    'removable'      => true
                ],
            ],
            $result
        );
    }

    public function getExtraFieldDefaultValue(string $fieldName): string
    {
        return 'def_opt';
    }

    public function testGetCalendarEvents()
    {
        $organizationId = 1;
        $userId = 123;
        $calendarId = 10;
        $start = new \DateTime();
        $end = new \DateTime();
        $subordinate = true;
        $allConnections = [
            ['calendarAlias' => 'provider1', 'calendar' => 10, 'visible' => true],
            ['calendarAlias' => 'provider1', 'calendar' => 20, 'visible' => false],
            ['calendarAlias' => 'provider2', 'calendar' => 10, 'visible' => false],
        ];

        $this->calendarPropertyProvider->expects($this->once())
            ->method('getItemsVisibility')
            ->with($calendarId, $subordinate)
            ->willReturn($allConnections);

        $this->provider1->expects($this->once())
            ->method('getCalendarEvents')
            ->with($organizationId, $userId, $calendarId, $start, $end, [10 => true, 20 => false])
            ->willReturn(
                [
                    [
                        'id'    => 1,
                        'title' => 'event1',
                    ],
                    [
                        'id'        => 2,
                        'title'     => 'event2',
                        'removable' => false
                    ],
                ]
            );
        $this->provider2->expects($this->once())
            ->method('getCalendarEvents')
            ->with($organizationId, $userId, $calendarId, $start, $end, [10 => false])
            ->willReturn(
                [
                    [
                        'id'    => 1,
                        'title' => 'event3',
                    ],
                    [
                        'id'        => 3,
                        'title'     => 'event4',
                        'editable'  => false,
                        'removable' => false,
                    ],
                ]
            );

        $result = $this->manager->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $subordinate);
        $this->assertEquals(
            [
                [
                    'id'            => 1,
                    'title'         => 'event1',
                    'calendarAlias' => 'provider1',
                    'editable'      => true,
                    'removable'     => true,
                ],
                [
                    'id'            => 2,
                    'title'         => 'event2',
                    'calendarAlias' => 'provider1',
                    'editable'      => true,
                    'removable'     => false,
                ],
                [
                    'id'            => 1,
                    'title'         => 'event3',
                    'calendarAlias' => 'provider2',
                    'editable'      => true,
                    'removable'     => true,
                ],
                [
                    'id'            => 3,
                    'title'         => 'event4',
                    'calendarAlias' => 'provider2',
                    'editable'      => false,
                    'removable'     => false,
                ],
            ],
            $result
        );
    }
}
