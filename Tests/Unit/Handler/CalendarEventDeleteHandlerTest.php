<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\CalendarEventDeleteHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class CalendarEventDeleteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $notificationManager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $deleteManager;

    /** @var CalendarEventDeleteHandler */
    protected $handler;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->calendarConfig = $this->getMockBuilder(SystemCalendarConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = $this->getMockBuilder(ApiEntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->notificationManager = $this->getMockBuilder(NotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getObjectManager')
            ->will($this->returnValue($objectManager));
        $ownerDeletionManager = $this->getMockBuilder(OwnerDeletionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = new RequestStack();
        $this->deleteManager = $this->getMockBuilder(DeleteManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new CalendarEventDeleteHandler();
        $this->handler->setCalendarConfig($this->calendarConfig);
        $this->handler->setAuthorizationChecker($this->authorizationChecker);
        $this->handler->setOwnerDeletionManager($ownerDeletionManager);
        $this->handler->setNotificationManager($this->notificationManager);
        $this->handler->setRequestStack($this->requestStack);
        $this->handler->setDeleteManager($this->deleteManager);
    }

    public function testHandleDelete()
    {
        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue(new CalendarEvent()));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Public calendars are disabled.
     */
    public function testHandleDeleteWhenPublicCalendarDisabled()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenPublicCalendarEventManagementNotGranted()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->will($this->returnValue(false));
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage System calendars are disabled.
     */
    public function testHandleDeleteWhenSystemCalendarDisabled()
    {
        $calendar = new SystemCalendar();
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenSystemCalendarEventManagementNotGranted()
    {
        $calendar = new SystemCalendar();
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($event));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue(false));
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->handleDelete(1, $this->manager);
    }

    public function testProcessDeleteShouldSendNotificationIfNotifyAttendeesIsAll()
    {
        $this->requestStack->push(new Request(['notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY]));

        $event = new CalendarEvent();
        $this->notificationManager->expects($this->once())
            ->method('onDelete')
            ->with($event, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->deleteManager->expects($this->once())
            ->method('deleteOrCancel')
            ->with($event, false);

        $this->handler->processDelete($event, $this->manager->getObjectManager());
    }

    public function testProcessDeleteShouldNotSendNotificationIfNotifyAttendeesIsNone()
    {
        $this->requestStack->push(new Request(['notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY]));

        $event = new CalendarEvent();
        $this->notificationManager->expects($this->once())
            ->method('onDelete')
            ->with($event, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->deleteManager->expects($this->once())
            ->method('deleteOrCancel')
            ->with($event, false);

        $this->handler->processDelete($event, $this->manager->getObjectManager());
    }

    public function testProcessDeleteShouldSendNotificationIfRequestIsNull()
    {
        $event = new CalendarEvent();
        $this->notificationManager->expects($this->once())
            ->method('onDelete')
            ->with($event, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->deleteManager->expects($this->once())
            ->method('deleteOrCancel')
            ->with($event, false);

        $this->handler->processDelete($event, $this->manager->getObjectManager());
    }

    public function testProcessDeleteShouldNotSendNotification()
    {
        $event = new CalendarEvent();
        $this->requestStack->push(new Request());

        $this->notificationManager->expects($this->once())
            ->method('onDelete')
            ->with($event, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->deleteManager->expects($this->once())
            ->method('deleteOrCancel')
            ->with($event, false);

        $this->handler->processDelete($event, $this->manager->getObjectManager());
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteInCaseIfUserHaveNoAccessToCallendar()
    {
        $calendarEvent = new CalendarEvent();
        $calendar = new Calendar();
        $calendarEvent->setCalendar($calendar);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendarEvent));

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $calendar)
            ->willReturn(false);

        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->handleDelete(1, $this->manager);
    }
}
