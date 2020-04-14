<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarProvider;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarEventNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $recurrenceModel;

    /** @var SystemCalendarProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper          = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarEventNormalizer =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarEventNormalizer')
                ->disableOriginalConstructor()
                ->getMock();
        $this->calendarConfig    =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->recurrenceModel =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence')
                ->disableOriginalConstructor()
                ->getMock();

        $this->provider = new SystemCalendarProvider(
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
        $userId         = 123;
        $calendarId     = 10;
        $calendarIds    = [10];

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

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
        $userId         = 123;
        $calendarId     = 10;
        $calendarIds    = [10, 20];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $organization1 = new Organization();
        $calendar1->setOrganization($organization1);
        $calendar1->setName('Main OroCRM');
        $calendar1->setBackgroundColor('#FF0000');

        $calendar2 = new SystemCalendar();
        ReflectionUtil::setId($calendar2, 2);
        $calendar2->setOrganization($organization1);
        $calendar2->setName('Second OroCRM');

        $calendars = [$calendar1, $calendar2];

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue(false));

        $repo  = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb    = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getSystemCalendarsQueryBuilder')
            ->with($organizationId)
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($calendars));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);

        $this->assertEquals(
            [
                $calendar1->getId() => [
                    'calendarName'    => $calendar1->getName(),
                    'backgroundColor' => $calendar1->getBackgroundColor(),
                    'removable'       => false,
                    'position'        => -60,
                ],
                $calendar2->getId() => [
                    'calendarName'    => $calendar2->getName(),
                    'backgroundColor' => $calendar2->getBackgroundColor(),
                    'removable'       => false,
                    'position'        => -60,
                ]
            ],
            $result
        );
    }

    public function testGetCalendarDefaultValuesCanAddEvents()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $calendarIds    = [10];

        $calendar1 = new SystemCalendar();
        ReflectionUtil::setId($calendar1, 1);
        $organization1 = new Organization();
        $calendar1->setOrganization($organization1);
        $calendar1->setName('Main OroCRM');
        $calendar1->setBackgroundColor('#FF0000');

        $calendars = [$calendar1];

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue(true));

        $repo  = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb    = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getSystemCalendarsQueryBuilder')
            ->with($organizationId)
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($calendars));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:SystemCalendar')
            ->will($this->returnValue($repo));

        $result = $this->provider->getCalendarDefaultValues($organizationId, $userId, $calendarId, $calendarIds);

        $this->assertEquals(
            [
                $calendar1->getId() => [
                    'calendarName'    => $calendar1->getName(),
                    'backgroundColor' => $calendar1->getBackgroundColor(),
                    'removable'       => false,
                    'position'        => -60,
                    'canAddEvent'     => true,
                    'canEditEvent'    => true,
                    'canDeleteEvent'  => true,
                ],
            ],
            $result
        );
    }

    public function testGetCalendarEventsDisabled()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $connections    = [10 => true, 20 => false];

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals([], $result);
    }

    public function testGetCalendarEventsDenied()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $connections    = [10 => true, 20 => false];

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue(false));

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals([], $result);
    }

    public function testGetCalendarEvents()
    {
        $organizationId = 1;
        $userId         = 123;
        $calendarId     = 10;
        $start          = new \DateTime();
        $end            = new \DateTime();
        $connections    = [10 => true, 20 => false];
        $events         = [['id' => 1]];

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue(true));

        $qb   = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroCalendarBundle:CalendarEvent')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('getSystemEventListByTimeIntervalQueryBuilder')
            ->with($this->identicalTo($start), $this->identicalTo($end))
            ->will($this->returnValue($qb));
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb->expects($this->at(0))
            ->method('andWhere')
            ->with('c.organization = :organizationId')
            ->will($this->returnSelf());
        $qb->expects($this->at(1))
            ->method('setParameter')
            ->with('organizationId', $organizationId)
            ->will($this->returnSelf());
        $qb->expects($this->at(2))
            ->method('andWhere')
            ->with('c.id NOT IN (:invisibleIds)')
            ->will($this->returnSelf());
        $qb->expects($this->at(3))
            ->method('setParameter')
            ->with('invisibleIds', [20])
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->calendarEventNormalizer->expects($this->once())
            ->method('getCalendarEvents')
            ->with($calendarId, $this->identicalTo($query))
            ->will($this->returnValue($events));

        $result = $this->provider->getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections);
        $this->assertEquals($events, $result);
    }
}
