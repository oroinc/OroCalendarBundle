<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\SystemCalendarDeleteHandlerExtension;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarDeleteHandlerExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var SystemCalendarConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarConfig;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var SystemCalendarDeleteHandlerExtension */
    private $extension;

    protected function setUp()
    {
        $this->calendarConfig = $this->createMock(SystemCalendarConfig::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->extension = new SystemCalendarDeleteHandlerExtension(
            $this->calendarConfig,
            $this->authorizationChecker,
            $this->tokenAccessor
        );
        $this->extension->setDoctrine($this->createMock(ManagerRegistry::class));
        $this->extension->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
    }

    public function testAssertDeleteGrantedWhenAccessGranted()
    {
        $calendarOrganization = new Organization();
        $calendarOrganization->setId(1);
        $calendar = new SystemCalendar();
        $calendar->setOrganization($calendarOrganization);

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn($calendarOrganization->getId());

        $this->extension->assertDeleteGranted($calendar);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage The delete operation is forbidden. Reason: public calendars are disabled.
     */
    public function testAssertDeleteGrantedWhenPublicCalendarDisabled()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(false);

        $this->extension->assertDeleteGranted($calendar);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage The delete operation is forbidden. Reason: access denied.
     */
    public function testAssertDeleteGrantedWhenPublicCalendarDeleteNotGranted()
    {
        $calendar = new SystemCalendar();
        $calendar->setPublic(true);

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_public_calendar_management')
            ->willReturn(false);

        $this->extension->assertDeleteGranted($calendar);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage The delete operation is forbidden. Reason: system calendars are disabled.
     */
    public function testAssertDeleteGrantedWhenSystemCalendarDisabled()
    {
        $calendar = new SystemCalendar();

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(false);

        $this->extension->assertDeleteGranted($calendar);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage The delete operation is forbidden. Reason: access denied.
     */
    public function testAssertDeleteGrantedWhenSystemCalendarDeleteNotGranted()
    {
        $calendar = new SystemCalendar();

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->willReturn(false);

        $this->extension->assertDeleteGranted($calendar);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @expectedExceptionMessage The delete operation is forbidden. Reason: access denied.
     */
    public function testAssertDeleteGrantedWhenSystemCalendarWasCreatedInAnotherOrganization()
    {
        $calendarOrganization = new Organization();
        $calendarOrganization->setId(1);
        $calendar = new SystemCalendar();
        $calendar->setOrganization($calendarOrganization);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(2);

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(true);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_system_calendar_management')
            ->willReturn(true);

        $this->extension->assertDeleteGranted($calendar);
    }
}
