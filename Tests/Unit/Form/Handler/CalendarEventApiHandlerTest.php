<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class CalendarEventApiHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailSendProcessor;

    /** @var CalendarEvent */
    protected $entity;

    /** @var Organization */
    protected $organization;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CalendarEventManager */
    protected $calendarEventManager;

    /** @var CalendarEventApiHandler */
    protected $handler;

    protected function setUp()
    {
        $this->entity  = new CalendarEvent();

        $formData = [
            'contexts' => [],
            'attendees' => new ArrayCollection()
        ];

        $this->request = new Request();
        $this->request->request = new ParameterBag($formData);

        $this->form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($formData));

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($objectManager));

        $this->organization = new Organization();
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->emailSendProcessor = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarEventManager = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));

        $objectManager->expects($this->once())
            ->method('flush');

        $this->handler = new CalendarEventApiHandler(
            $this->form,
            $this->request,
            $doctrine,
            $securityFacade,
            $this->emailSendProcessor,
            $this->activityManager,
            $this->calendarEventManager
        );
    }

    public function testProcessWithContexts()
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $this->request->setMethod('POST');

        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity->setCalendar($defaultCalendar);

        $defaultCalendar->expects($this->once())
            ->method('getOwner')
            ->will($this->returnValue($owner));


        $this->setExpectedFormValues(['contexts' => [$context]]);

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with(
                $this->entity,
                [$context, $owner]
            );

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->handler->process($this->entity);

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    public function testProcessPutWithNotifyInvitedUsersWorks()
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(['notifyInvitedUsers' => true]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->emailSendProcessor
            ->expects($this->once())
            ->method('sendUpdateParentEventNotification')
            ->with($this->entity, clone $this->entity, true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyInvitedUsersFalseWorks()
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues(['notifyInvitedUsers' => false]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->emailSendProcessor
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyInvitedUsersNotPassedWorks()
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues([]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->emailSendProcessor
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyInvitedUsersWorks()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyInvitedUsers' => true]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->emailSendProcessor
            ->expects($this->once())
            ->method('sendInviteNotification')
            ->with($this->entity);

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyInvitedUsersFalseWorks()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyInvitedUsers' => false]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->emailSendProcessor
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyInvitedUsersNotPassedWorks()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues([]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->emailSendProcessor
            ->expects($this->never())
            ->method($this->anything());

        $this->handler->process($this->entity);
    }

    public function testProcessWithClearingExceptions()
    {
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues(['updateExceptions' => true]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->handler->process($this->entity);
    }

    /**
     * @param array $values
     */
    protected function setExpectedFormValues(array $values)
    {
        $fields = ['contexts', 'notifyInvitedUsers', 'updateExceptions'];

        $valueMapHas = [];

        foreach ($fields as $name) {
            $valueMapHas[] = [$name, isset($values[$name])];
        }

        $this->form->expects($this->any())
            ->method('has')
            ->willReturnMap($valueMapHas);

        $valueMapGet = [];

        foreach ($values as $name => $value) {
            $field = $this->createMock('Symfony\Component\Form\FormInterface');
            $field->expects($this->any())
                ->method('getData')
                ->willReturn($value);
            $valueMapGet[] = [$name, $field];
        }

        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap($valueMapGet);
    }
}
