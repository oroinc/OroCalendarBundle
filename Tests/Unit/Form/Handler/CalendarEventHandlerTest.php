<?php
declare(strict_types=1);

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CalendarEventHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var Form|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Form|\PHPUnit\Framework\MockObject\MockObject */
    private $notifyAttendeesForm;

    /** @var Request */
    private $request;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventManager;

    /** @var NotificationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var Organization */
    private $organization;

    /** @var CalendarEvent */
    private $entity;

    /** @var CalendarEventHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->notifyAttendeesForm = $this->createMock(Form::class);
        $this->request = new Request();
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
        $this->notificationManager = $this->createMock(NotificationManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->organization = new Organization();
        $this->entity = new CalendarEvent();

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->objectManager);

        $this->notifyAttendeesForm->expects(self::any())
            ->method('getData')
            ->willReturn(NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->handler = new CalendarEventHandler(
            $requestStack,
            $doctrine,
            $this->tokenAccessor,
            $this->activityManager,
            $this->calendarEventManager,
            $this->notificationManager,
            $this->featureChecker
        );

        $this->handler->setForm($this->form);
        $this->handler->setEntityRoutingHelper($this->entityRoutingHelper);
    }

    public function testProcessGetRequestWithCalendar(): void
    {
        $calendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($calendar);

        $this->form->expects(self::never())
            ->method('submit');

        self::assertFalse($this->handler->process($this->entity));
    }

    public function testProcessWithExceptionWithParent(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->entity->setParent(new CalendarEvent());
        $this->handler->process($this->entity);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData(string $method): void
    {
        $calendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($calendar);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->entity));
        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::identicalTo(self::FORM_DATA));
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);
        $this->objectManager->expects(self::never())
            ->method('persist');
        $this->objectManager->expects(self::never())
            ->method('flush');
        $this->calendarEventManager->expects(self::never())
            ->method('onEventUpdate');

        self::assertFalse($this->handler->process($this->entity));
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithoutTargetEntity(string $method): void
    {
        $calendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($calendar);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->entity));
        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::identicalTo(self::FORM_DATA));
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form->expects(self::any())
            ->method('has')
            ->willReturn(false);
        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntityClassName')
            ->willReturn(null);
        $this->calendarEventManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);
        $this->objectManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($this->entity));
        $this->objectManager->expects(self::once())
            ->method('flush');

        self::assertTrue($this->handler->process($this->entity));
    }

    /**
     * @dataProvider supportedMethods
     */
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
        $this->form->expects(self::any())
            ->method('get')
            ->willReturnCallback(fn ($p) => 'notifyAttendees' === $p ? $this->notifyAttendeesForm : $this->form);

        $defaultCalendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->form->expects(self::any())
            ->method('has')
            ->withConsecutive(
                ['contexts'],
                ['notifyAttendees']
            )
            ->willReturn(true);

        $defaultCalendar->expects(self::once())
            ->method('getOwner')
            ->willReturn($owner);

        $this->form->expects(self::any())
            ->method('getData')
            ->willReturn([$context]);

        $this->activityManager->expects(self::once())
            ->method('setActivityTargets')
            ->with(
                self::identicalTo($this->entity),
                self::identicalTo([$context, $owner])
            );

        $this->activityManager->expects(self::never())
            ->method('removeActivityTarget');

        self::assertTrue($this->handler->process($this->entity));
        self::assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessRequestWithoutCurrentUser(string $method): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Both logged in user and organization must be defined.');

        $this->request->setMethod($method);

        $this->form->expects(self::never())
            ->method('submit')
            ->with(self::identicalTo(self::FORM_DATA));

        $this->tokenAccessor->expects(self::once())
            ->method('getUserId')
            ->willReturn(null);

        $this->handler->process($this->entity);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithTargetEntityAssign(string $method): void
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntityClassName')
            ->willReturn(get_class($targetEntity));
        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntityId')
            ->willReturn($targetEntity->getId());
        $this->entityRoutingHelper->expects(self::once())
            ->method('getAction')
            ->willReturn('assign');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->entity));
        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::identicalTo(self::FORM_DATA));
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->willReturn($targetEntity);

        $this->activityManager->expects(self::never())
            ->method('addActivityTarget')
            ->with(self::identicalTo($this->entity), self::identicalTo($targetEntity));

        $this->tokenAccessor->expects(self::once())
            ->method('getUserId')
            ->willReturn(100);

        $repository = $this->createMock(CalendarRepository::class);

        $calendar = $this->createMock(Calendar::class);

        $repository->expects(self::once())
            ->method('findDefaultCalendar')
            ->willReturn($calendar);

        $this->objectManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->form->expects(self::any())
            ->method('has')
            ->willReturn(false);

        $this->calendarEventManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->objectManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($this->entity));
        $this->objectManager->expects(self::once())
            ->method('flush');

        self::assertTrue($this->handler->process($this->entity));
        self::assertNotSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithTargetEntityActivity(string $method): void
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntityClassName')
            ->willReturn(get_class($targetEntity));
        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntityId')
            ->willReturn($targetEntity->getId());
        $this->entityRoutingHelper->expects(self::once())
            ->method('getAction')
            ->willReturn('activity');

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->entity));
        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::identicalTo(self::FORM_DATA));
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->entityRoutingHelper->expects(self::once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->willReturn($targetEntity);

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with(self::identicalTo($this->entity), self::identicalTo($targetEntity));
        $this->form->expects(self::any())
            ->method('has')
            ->willReturn(false);

        $this->calendarEventManager->expects(self::once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->objectManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($this->entity));
        $this->objectManager->expects(self::once())
            ->method('flush');

        self::assertTrue($this->handler->process($this->entity));
        self::assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    public function supportedMethods(): array
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
