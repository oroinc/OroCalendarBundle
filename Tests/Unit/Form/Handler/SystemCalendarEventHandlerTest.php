<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarEventHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SystemCalendarEventHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var Form|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request */
    private $request;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventManager;

    /** @var NotificationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CalendarEvent */
    private $entity;

    /** @var Organization */
    private $organization;

    /** @var SystemCalendarEventHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->request = new Request();
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
        $this->notificationManager = $this->createMock(NotificationManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->entity = new CalendarEvent();
        $this->organization = new Organization();

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->objectManager);

        $this->handler = new SystemCalendarEventHandler(
            $requestStack,
            $doctrine,
            $tokenAccessor,
            $this->activityManager,
            $this->calendarEventManager,
            $this->notificationManager,
            $this->featureChecker
        );
        $this->handler->setForm($this->form);
    }

    public function testProcessWithContexts()
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $this->request->setMethod('POST');
        $defaultCalendar = $this->createMock(Calendar::class);
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects($this->any())
            ->method('get')
            ->willReturn($this->form);

        $this->form->expects($this->once())
            ->method('has')
            ->with('contexts')
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $defaultCalendar->expects($this->once())
            ->method('getOwner')
            ->willReturn($owner);

        $this->form->expects($this->any())
            ->method('getData')
            ->willReturn([$context]);

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with(
                $this->identicalTo($this->entity),
                $this->identicalTo([$context, $owner])
            );

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');
        $this->handler->process($this->entity);

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData(string $method)
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo(self::FORM_DATA));
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->calendarEventManager->expects($this->never())
            ->method($this->anything());
        $this->objectManager->expects($this->never())
            ->method($this->anything());

        $this->assertFalse(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidData(string $method)
    {
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo(self::FORM_DATA));
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);
        $this->objectManager->expects($this->once())
            ->method('persist');
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );
    }

    public function supportedMethods(): array
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
