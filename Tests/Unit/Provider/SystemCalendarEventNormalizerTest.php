<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarEventNormalizer;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarEventNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventManager;

    /** @var ReminderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $reminderManager;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var SystemCalendarEventNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
        $this->reminderManager = $this->createMock(ReminderManager::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $attendeeManager = $this->createMock(AttendeeManager::class);
        $attendeeManager->expects($this->any())
            ->method('getAttendeeListsByCalendarEventIds')
            ->willReturnCallback(function (array $calendarEventIds) {
                return array_fill_keys($calendarEventIds, []);
            });

        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects($this->any())
            ->method('sanitize')
            ->willReturnCallback(function ($value) {
                return $value ? $value . 's' : $value;
            });

        $this->normalizer = new SystemCalendarEventNormalizer(
            $this->calendarEventManager,
            $attendeeManager,
            $this->reminderManager,
            $this->authorizationChecker,
            $htmlTagHelper
        );
    }

    /**
     * @dataProvider getCalendarEventsProvider
     */
    public function testGetCalendarEvents(array $events, array $expected)
    {
        $calendarId = 123;

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($events);

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, CalendarEvent::class);

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getGrantedCalendarEventsProvider
     */
    public function testGetCalendarEventsWithGrantedManagement(array $events, array $expected)
    {
        $calendarId = 123;

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($events);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, CalendarEvent::class);

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    public function getCalendarEventsProvider(): array
    {
        $startDate = new \DateTime();
        $endDate = $startDate->add(new \DateInterval('PT1H'));

        return [
            [
                'events'   => [],
                'expected' => []
            ],
            [
                'events'   => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'description' => 'description',
                        'start'    => $startDate,
                        'end'      => $endDate
                    ],
                ],
                'expected' => [
                    [
                        'calendar'  => 123,
                        'id'        => 1,
                        'title'     => 'test',
                        'description' => 'descriptions',
                        'start'     => $startDate->format('c'),
                        'end'       => $endDate->format('c'),
                        'attendees' => [],
                        'editable'  => false,
                        'removable' => false
                    ],
                ]
            ],
            [
                'events'   => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'description' => 'description',
                        'start'    => $startDate,
                        'end'      => $endDate
                    ],
                ],
                'expected' => [
                    [
                        'calendar'  => 123,
                        'id'        => 1,
                        'title'     => 'test',
                        'description' => 'descriptions',
                        'start'     => $startDate->format('c'),
                        'end'       => $endDate->format('c'),
                        'attendees' => [],
                        'editable'  => false,
                        'removable' => false
                    ],
                ]
            ],
        ];
    }

    public function getGrantedCalendarEventsProvider(): array
    {
        $startDate = new \DateTime();
        $endDate = $startDate->add(new \DateInterval('PT1H'));

        return [
            [
                'events'   => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'start'    => $startDate,
                        'end'      => $endDate
                    ],
                ],
                'expected' => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'start'    => $startDate->format('c'),
                        'end'      => $endDate->format('c'),
                        'attendees' => [],
                    ],
                ]
            ],
        ];
    }
}
