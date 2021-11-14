<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\CalendarEventDeleteHandler;
use Oro\Bundle\CalendarBundle\Handler\CalendarEventDeleteHandlerExtension;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CalendarEventDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SystemCalendarConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarConfig;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var DeleteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $deleteManager;

    /** @var NotificationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationManager;

    /** @var CalendarEventDeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->calendarConfig = $this->createMock(SystemCalendarConfig::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->deleteManager = $this->createMock(DeleteManager::class);
        $this->notificationManager = $this->createMock(NotificationManager::class);

        $accessDeniedExceptionFactory = new EntityDeleteAccessDeniedExceptionFactory();

        $extension = new CalendarEventDeleteHandlerExtension(
            $this->calendarConfig,
            $this->authorizationChecker,
            $this->notificationManager
        );
        $extension->setDoctrine($this->doctrine);
        $extension->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects($this->any())
            ->method('getHandlerExtension')
            ->with(CalendarEvent::class)
            ->willReturn($extension);

        $this->handler = new CalendarEventDeleteHandler(
            $this->deleteManager
        );
        $this->handler->setDoctrine($this->doctrine);
        $this->handler->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $this->handler->setExtensionRegistry($extensionRegistry);
    }

    public function testDeleteWhenPublicCalendarDisabled()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: public calendars are disabled.');

        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(false);
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->delete($event);
    }

    public function testDeleteWhenPublicCalendarEventManagementNotGranted()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

        $calendar = new SystemCalendar();
        $calendar->setPublic(true);
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->willReturn(false);
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->delete($event);
    }

    public function testDeleteWhenSystemCalendarDisabled()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: system calendars are disabled.');

        $calendar = new SystemCalendar();
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(false);
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->delete($event);
    }

    public function testDeleteWhenSystemCalendarEventManagementNotGranted()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

        $calendar = new SystemCalendar();
        $event = new CalendarEvent();
        $event->setSystemCalendar($calendar);

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->willReturn(false);
        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->delete($event);
    }

    public function testDeleteShouldSendNotificationIfNotifyAttendeesIsAll()
    {
        $event = new CalendarEvent();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->deleteManager->expects($this->once())
            ->method('deleteOrCancel')
            ->with($event, false);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(CalendarEvent::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('contains')
            ->with($this->identicalTo($event))
            ->willReturn(false);
        $em->expects($this->once())
            ->method('flush');

        $this->notificationManager->expects($this->once())
            ->method('onDelete')
            ->with($event, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);

        $this->handler->delete($event, true, ['notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY]);
    }

    public function testDeleteShouldNotSendNotificationIfNotifyAttendeesIsNone()
    {
        $event = new CalendarEvent();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->deleteManager->expects($this->once())
            ->method('deleteOrCancel')
            ->with($event, false);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(CalendarEvent::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('contains')
            ->with($this->identicalTo($event))
            ->willReturn(false);
        $em->expects($this->once())
            ->method('flush');

        $this->notificationManager->expects($this->once())
            ->method('onDelete')
            ->with($event, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->handler->delete($event, true, ['notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY]);
    }

    public function testDeleteShouldSendNotificationIfNotifyStrategyIsNotSet()
    {
        $event = new CalendarEvent();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->deleteManager->expects($this->once())
            ->method('deleteOrCancel')
            ->with($event, false);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(CalendarEvent::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('contains')
            ->with($this->identicalTo($event))
            ->willReturn(false);
        $em->expects($this->once())
            ->method('flush');

        $this->notificationManager->expects($this->once())
            ->method('onDelete')
            ->with($event, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);

        $this->handler->delete($event);
    }

    public function testDeleteInCaseIfUserHaveNoAccessToCalendar()
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

        $calendar = new Calendar();
        $event = new CalendarEvent();
        $event->setCalendar($calendar);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $calendar)
            ->willReturn(false);

        $this->deleteManager->expects($this->never())
            ->method($this->anything());

        $this->handler->delete($event);
    }
}
