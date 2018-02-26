<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\SystemCalendarDeleteHandler;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class SystemCalendarDeleteHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $tokenAccessor;

    /** @var SystemCalendarDeleteHandler */
    protected $handler;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->calendarConfig = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager        = $this->getMockBuilder('Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager        = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager->expects($this->any())
            ->method('getObjectManager')
            ->will($this->returnValue($objectManager));
        $ownerDeletionManager = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->handler = new SystemCalendarDeleteHandler();
        $this->handler->setCalendarConfig($this->calendarConfig);
        $this->handler->setAuthorizationChecker($this->authorizationChecker);
        $this->handler->setOwnerDeletionManager($ownerDeletionManager);
        $this->handler->setTokenAccessor($this->tokenAccessor);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Public calendars are disabled.
     */
    public function testHandleDeleteWhenPublicCalendarDisabled()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenPublicCalendarDeleteNotGranted()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage System calendars are disabled.
     */
    public function testHandleDeleteWhenSystemCalendarDisabled()
    {
        $calendar = new SystemCalendar();

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenSystemCalendarDeleteNotGranted()
    {
        $calendar = new SystemCalendar();

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue(false));

        $this->handler->handleDelete(1, $this->manager);
    }

    /**
     * @expectedException \Oro\Bundle\SecurityBundle\Exception\ForbiddenException
     * @expectedExceptionMessage Access denied.
     */
    public function testHandleDeleteWhenSystemCalendarWasCreatedInAnotherOrganization()
    {
        $calendarOrganization = new Organization();
        $calendarOrganization->setId(1);
        $calendar = new SystemCalendar();
        $calendar->setOrganization($calendarOrganization);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(2);

        $this->manager->expects($this->once())
            ->method('find')
            ->will($this->returnValue($calendar));
        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->will($this->returnValue(true));

        $this->handler->handleDelete(1, $this->manager);
    }
}
