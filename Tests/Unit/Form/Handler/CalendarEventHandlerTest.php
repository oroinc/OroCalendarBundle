<?php
declare(strict_types=1);

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CalendarEventHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var MockObject|Form */
    private $form;

    /** @var MockObject|Form */
    private $notifyAttendeesForm;

    /** @var RequestStack */
    private $requestStack;

    /** @var Request */
    private $request;

    /** @var MockObject */
    private $objectManager;

    /** @var MockObject */
    private $activityManager;

    /** @var MockObject */
    private $entityRoutingHelper;

    /** @var MockObject */
    private $tokenAccessor;

    /** @var MockObject|CalendarEventManager */
    private $calendarEventManager;

    /** @var CalendarEventHandler */
    private $handler;

    /** @var CalendarEvent */
    private $entity;

    /** @var Organization */
    private $organization;

    /** @var MockObject */
    private $notificationManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->notifyAttendeesForm = $this->createMock(Form::class);
        $this->notifyAttendeesForm->method('getData')->willReturn(NotificationManager::NONE_NOTIFICATIONS_STRATEGY);
        $this->request = new Request();
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->objectManager = $this->createMock(ObjectManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);

        $doctrine->method('getManager')->willReturn($this->objectManager);

        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);

        $this->organization = new Organization();
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->method('getOrganization')->willReturn($this->organization);

        $this->notificationManager = $this->createMock(NotificationManager::class);
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->entity = new CalendarEvent();
        $this->handler = new CalendarEventHandler(
            $this->requestStack,
            $doctrine,
            $this->tokenAccessor,
            $this->activityManager,
            $this->calendarEventManager,
            $this->notificationManager
        );
        $this->handler->setFeatureChecker($this->featureChecker);

        $this->handler->setForm($this->form);
        $this->handler->setEntityRoutingHelper($this->entityRoutingHelper);
    }

    public function testProcessGetRequestWithCalendar(): void
    {
        $calendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($calendar);

        $this->form->expects(static::never())->method('submit');

        static::assertFalse($this->handler->process($this->entity));
    }

    public function testProcessWithExceptionWithParent(): void
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->entity->setParent(new CalendarEvent());
        $this->handler->process($this->entity);
    }

    /** @dataProvider supportedMethods */
    public function testProcessInvalidData(string $method): void
    {
        $calendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($calendar);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(static::once())->method('setData')->with(static::identicalTo($this->entity));
        $this->form->expects(static::once())->method('submit')->with(static::identicalTo(self::FORM_DATA));
        $this->form->expects(static::once())->method('isValid')->willReturn(false);
        $this->objectManager->expects(static::never())->method('persist');
        $this->objectManager->expects(static::never())->method('flush');
        $this->calendarEventManager->expects(static::never())->method('onEventUpdate');

        static::assertFalse($this->handler->process($this->entity));
    }

    /** @dataProvider supportedMethods */
    public function testProcessValidDataWithoutTargetEntity(string $method): void
    {
        $calendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($calendar);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(static::once())->method('setData')->with(static::identicalTo($this->entity));
        $this->form->expects(static::once())->method('submit')->with(static::identicalTo(self::FORM_DATA));
        $this->form->expects(static::once())->method('isValid')->willReturn(true);
        $this->form->method('has')->willReturn(false);
        $this->entityRoutingHelper->expects(static::once())->method('getEntityClassName')->willReturn(null);
        $this->calendarEventManager->expects(static::once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);
        $this->objectManager->expects(static::once())->method('persist')->with(static::identicalTo($this->entity));
        $this->objectManager->expects(static::once())->method('flush');

        static::assertTrue($this->handler->process($this->entity));
    }

    /** @dataProvider supportedMethods */
    public function testProcessWithContexts(string $method): void
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $owner->setOrganization($organization);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);
        $this->form->method('get')->willReturnCallback(
            fn ($p) => 'notifyAttendees' === $p ? $this->notifyAttendeesForm : $this->form
        );

        $defaultCalendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects(static::once())->method('isValid')->willReturn(true);

        $this->form->expects(static::any())
            ->method('has')
            ->withConsecutive(
                ['contexts'],
                ['notifyAttendees']
            )
            ->willReturn(true);

        $defaultCalendar->expects(static::once())->method('getOwner')->willReturn($owner);

        $this->form->method('getData')->willReturn([$context]);

        $this->activityManager->expects(static::once())
            ->method('setActivityTargets')
            ->with(
                static::identicalTo($this->entity),
                static::identicalTo([$context, $owner])
            );

        $this->activityManager->expects(static::never())->method('removeActivityTarget');

        static::assertTrue($this->handler->process($this->entity));
        static::assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    /** @dataProvider supportedMethods */
    public function testProcessRequestWithoutCurrentUser(string $method): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Both logged in user and organization must be defined.');

        $this->request->setMethod($method);

        $this->form->expects(static::never())->method('submit')->with(static::identicalTo(self::FORM_DATA));

        $this->tokenAccessor->expects(static::once())->method('getUserId')->willReturn(null);

        $this->handler->process($this->entity);
    }

    /** @dataProvider supportedMethods */
    public function testProcessValidDataWithTargetEntityAssign(string $method): void
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects(static::once())
            ->method('getEntityClassName')
            ->willReturn(get_class($targetEntity));
        $this->entityRoutingHelper->expects(static::once())->method('getEntityId')->willReturn($targetEntity->getId());
        $this->entityRoutingHelper->expects(static::once())->method('getAction')->willReturn('assign');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(static::once())->method('setData')->with(static::identicalTo($this->entity));
        $this->form->expects(static::once())->method('submit')->with(static::identicalTo(self::FORM_DATA));
        $this->form->expects(static::once())->method('isValid')->willReturn(true);

        $this->entityRoutingHelper->expects(static::once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->willReturn($targetEntity);

        $this->activityManager->expects(static::never())
            ->method('addActivityTarget')
            ->with(static::identicalTo($this->entity), static::identicalTo($targetEntity));

        $this->tokenAccessor->expects(static::once())->method('getUserId')->willReturn(100);

        /** @var CalendarRepository|MockObject $repository */
        $repository = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findAll', 'findBy', 'findOneBy', 'getClassName'])
            ->addMethods(['findDefaultCalendar'])
            ->getMock();

        $calendar = $this->createMock(Calendar::class);

        $repository ->expects(static::once())
            ->method('findDefaultCalendar')
            ->willReturn($calendar);

        $this->objectManager->expects(static::once())->method('getRepository')->willReturn($repository);

        $this->form->method('has')->willReturn(false);

        $this->calendarEventManager->expects(static::once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->objectManager->expects(static::once())->method('persist')->with(static::identicalTo($this->entity));
        $this->objectManager->expects(static::once())->method('flush');

        static::assertTrue($this->handler->process($this->entity));
        static::assertNotSame($defaultCalendar, $this->entity->getCalendar());
    }

    /** @dataProvider supportedMethods */
    public function testProcessValidDataWithTargetEntityActivity(string $method): void
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects(static::once())
            ->method('getEntityClassName')
            ->willReturn(get_class($targetEntity));
        $this->entityRoutingHelper->expects(static::once())->method('getEntityId')->willReturn($targetEntity->getId());
        $this->entityRoutingHelper->expects(static::once())->method('getAction')->willReturn('activity');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(static::once())->method('setData')->with(static::identicalTo($this->entity));
        $this->form->expects(static::once())->method('submit')->with(static::identicalTo(self::FORM_DATA));
        $this->form->expects(static::once())->method('isValid')->willReturn(true);

        $this->entityRoutingHelper->expects(static::once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->willReturn($targetEntity);

        $this->activityManager->expects(static::once())
            ->method('addActivityTarget')
            ->with(static::identicalTo($this->entity), static::identicalTo($targetEntity));
        $this->form->method('has')->willReturn(false);

        $this->calendarEventManager->expects(static::once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->objectManager->expects(static::once())->method('persist')->with(static::identicalTo($this->entity));
        $this->objectManager->expects(static::once())->method('flush');

        static::assertTrue($this->handler->process($this->entity));
        static::assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    public function supportedMethods(): array
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
