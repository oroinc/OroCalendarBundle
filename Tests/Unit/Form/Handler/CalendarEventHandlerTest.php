<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CalendarEventHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $objectManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $activityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityRoutingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CalendarEventManager */
    protected $calendarEventManager;

    /** @var CalendarEventHandler */
    protected $handler;

    /** @var CalendarEvent */
    protected $entity;

    /** @var Organization */
    protected $organization;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $notificationManager;

    protected function setUp()
    {
        $this->form                = $this->createMock('Symfony\Component\Form\Form');
        $this->request = new Request();
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->objectManager       = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->objectManager));

        $this->activityManager     = $this->getMockBuilder(ActivityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRoutingHelper = $this->getMockBuilder(EntityRoutingHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->organization = new Organization();
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->notificationManager = $this->getMockBuilder(NotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarEventManager = $this
            ->getMockBuilder(CalendarEventManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = new CalendarEvent();

        $this->handler = new CalendarEventHandler(
            $this->requestStack,
            $doctrine,
            $this->tokenAccessor,
            $this->activityManager,
            $this->calendarEventManager,
            $this->notificationManager
        );

        $this->handler->setForm($this->form);
        $this->handler->setEntityRoutingHelper($this->entityRoutingHelper);
    }

    public function testProcessGetRequestWithCalendar()
    {
        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($calendar);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessWithExceptionWithParent()
    {
        $this->entity->setParent(new CalendarEvent());
        $this->handler->process($this->entity);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData($method)
    {
        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($calendar);

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
            ->will($this->returnValue(false));
        $this->objectManager->expects($this->never())
            ->method('persist');
        $this->objectManager->expects($this->never())
            ->method('flush');
        $this->calendarEventManager->expects($this->never())
            ->method('onEventUpdate');

        $this->assertFalse(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithoutTargetEntity($method)
    {
        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($calendar);

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
            ->will($this->returnValue(true));
        $this->form->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityClassName')
            ->will($this->returnValue(null));
        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);
        $this->objectManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessWithContexts($method)
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
        $this->form->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->form));

        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->form->expects($this->any())
            ->method('has')
            ->withConsecutive(
                ['contexts'],
                ['notifyAttendees']
            )
            ->will($this->returnValue(true));

        $defaultCalendar->expects($this->once())
            ->method('getOwner')
            ->will($this->returnValue($owner));

        $this->form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue([$context]));

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with(
                $this->identicalTo($this->entity),
                $this->identicalTo([$context, $owner])
            );

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');
        $this->assertTrue(
            $this->handler->process($this->entity)
        );

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }


    /**
     * @dataProvider supportedMethods
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Both logged in user and organization must be defined.
     */
    public function testProcessRequestWithoutCurrentUser($method)
    {
        $this->request->setMethod($method);

        $this->form->expects($this->never())
            ->method('submit')
            ->with($this->identicalTo(self::FORM_DATA));

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->will($this->returnValue(null));

        $this->handler->process($this->entity);
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithTargetEntityAssign($method)
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityClassName')
            ->will($this->returnValue(get_class($targetEntity)));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityId')
            ->will($this->returnValue($targetEntity->getId()));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue('assign'));

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
            ->will($this->returnValue(true));

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->will($this->returnValue($targetEntity));

        $this->activityManager->expects($this->never())
            ->method('addActivityTarget')
            ->with($this->identicalTo($this->entity), $this->identicalTo($targetEntity));

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->will($this->returnValue(100));

        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('find', 'findAll', 'findBy', 'findOneBy', 'getClassName', 'findDefaultCalendar'))
            ->getMock();

        $calendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();

        $repository ->expects($this->once())
            ->method('findDefaultCalendar')
            ->will($this->returnValue($calendar));

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $this->form->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->objectManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );

        $this->assertNotSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidDataWithTargetEntityActivity($method)
    {
        $targetEntity = new User();
        ReflectionUtil::setId($targetEntity, 123);
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $targetEntity->setOrganization($organization);
        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityClassName')
            ->will($this->returnValue(get_class($targetEntity)));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityId')
            ->will($this->returnValue($targetEntity->getId()));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getAction')
            ->will($this->returnValue('activity'));

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
            ->will($this->returnValue(true));

        $this->entityRoutingHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(get_class($targetEntity), $targetEntity->getId())
            ->will($this->returnValue($targetEntity));

        $this->activityManager->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($this->entity), $this->identicalTo($targetEntity));
        $this->form->expects($this->any())
            ->method('has')
            ->will($this->returnValue(false));

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->objectManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
