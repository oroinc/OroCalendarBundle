<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Provider\SystemCalendarEventNormalizer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarEventNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarEventManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attendeeManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $reminderManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var SystemCalendarEventNormalizer */
    protected $normalizer;

    protected function setUp(): void
    {
        $this->calendarEventManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attendeeManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attendeeManager->expects($this->any())
            ->method('getAttendeeListsByCalendarEventIds')
            ->will($this->returnCallback(function (array $calendarEventIds) {
                return array_fill_keys($calendarEventIds, []);
            }));

        $this->reminderManager = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject $htmlTagHelper */
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagHelper->expects($this->any())
            ->method('sanitize')
            ->willReturnCallback(
                function ($value) {
                    return $value ? $value . 's' : $value;
                }
            );

        $this->normalizer = new SystemCalendarEventNormalizer(
            $this->calendarEventManager,
            $this->attendeeManager,
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

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($events));

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getGrantedCalendarEventsProvider
     */
    public function testGetCalendarEventsWithGrantedManagement(array $events, array $expected)
    {
        $calendarId = 123;

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($events));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));
        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvents($calendarId, $query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

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

    /**
     * @return array
     */
    public function getGrantedCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

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
