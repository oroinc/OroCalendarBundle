<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventApiHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $form;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var RequestStack */
    protected $requestStack;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $notificationManager;

    /** @var CalendarEvent */
    protected $entity;

    /** @var Organization */
    protected $organization;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CalendarEventManager */
    protected $calendarEventManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CalendarEventApiHandler */
    protected $handler;

    protected function setUp(): void
    {
        $this->entity  = new CalendarEvent();

        $formData = [
            'contexts' => [],
            'attendees' => new ArrayCollection()
        ];

        $this->request = new Request();
        $this->request->request = new ParameterBag($formData);
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

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

        $doctrine = $this->createMock('Doctrine\Persistence\ManagerRegistry');

        $objectManager = $this->createMock('Doctrine\Persistence\ObjectManager');

        $doctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($objectManager));

        $this->organization = new Organization();
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->notificationManager = $this->getMockBuilder(NotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->activityManager = $this->getMockBuilder(ActivityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarEventManager = $this
            ->getMockBuilder(CalendarEventManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));

        $objectManager->expects($this->once())
            ->method('flush');

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->handler = new CalendarEventApiHandler(
            $this->requestStack,
            $doctrine,
            $tokenAccessor,
            $this->activityManager,
            $this->calendarEventManager,
            $this->notificationManager
        );
        $this->handler->setFeatureChecker($this->featureChecker);

        $this->handler->setForm($this->form);
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

    public function testProcessPutWithNotifyAttendeesAllWorks()
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesAllWorksAndDisabledInvitations()
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->never())
            ->method('onUpdate');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(false);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesAddedOrDeletedWorks()
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(
            [
                'notifyAttendees' => NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
            ]
        );

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesNoneWorks()
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues([
            'notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY
        ]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesNotPassedWorks()
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues([]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyAttendeesNoneWorks()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->once())
            ->method('onCreate')
            ->with($this->entity, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyAttendeesNoneWorksAndDisabledInvitations()
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager
            ->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager
            ->expects($this->never())
            ->method('onCreate');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(false);

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

    protected function setExpectedFormValues(array $values)
    {
        $fields = ['contexts', 'notifyAttendees', 'updateExceptions'];

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
