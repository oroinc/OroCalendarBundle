<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Handler\SystemCalendarDeleteHandlerExtension;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SystemCalendarDeleteHandlerExtensionTest extends TestCase
{
    private SystemCalendarConfig&MockObject $calendarConfig;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private SystemCalendarDeleteHandlerExtension $extension;

    #[\Override]
    protected function setUp(): void
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

    public function testAssertDeleteGrantedWhenAccessGranted(): void
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

    public function testAssertDeleteGrantedWhenPublicCalendarDisabled(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: public calendars are disabled.');

        $calendar = new SystemCalendar();
        $calendar->setPublic(true);

        $this->calendarConfig->expects($this->once())
            ->method('isPublicCalendarEnabled')
            ->willReturn(false);

        $this->extension->assertDeleteGranted($calendar);
    }

    public function testAssertDeleteGrantedWhenPublicCalendarDeleteNotGranted(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

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

    public function testAssertDeleteGrantedWhenSystemCalendarDisabled(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: system calendars are disabled.');

        $calendar = new SystemCalendar();

        $this->calendarConfig->expects($this->once())
            ->method('isSystemCalendarEnabled')
            ->willReturn(false);

        $this->extension->assertDeleteGranted($calendar);
    }

    public function testAssertDeleteGrantedWhenSystemCalendarDeleteNotGranted(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

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

    public function testAssertDeleteGrantedWhenSystemCalendarWasCreatedInAnotherOrganization(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The delete operation is forbidden. Reason: access denied.');

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
