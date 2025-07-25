<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CalendarEventApiHandlerTest extends TestCase
{
    private FormInterface&MockObject $form;
    private Request $request;
    private ActivityManager&MockObject $activityManager;
    private CalendarEventManager&MockObject $calendarEventManager;
    private NotificationManager&MockObject $notificationManager;
    private FeatureChecker&MockObject $featureChecker;
    private CalendarEvent $entity;
    private Organization $organization;
    private CalendarEventApiHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
        $this->notificationManager = $this->createMock(NotificationManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->entity = new CalendarEvent();
        $this->organization = new Organization();

        $formData = [
            'contexts' => [],
            'attendees' => new ArrayCollection()
        ];
        $this->request->request = new ParameterBag($formData);
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($formData));
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->entity));
        $entityManager->expects($this->once())
            ->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->handler = new CalendarEventApiHandler(
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

    public function testProcessWithContexts(): void
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $this->request->setMethod('POST');

        $defaultCalendar = $this->createMock(Calendar::class);

        $this->entity->setCalendar($defaultCalendar);

        $defaultCalendar->expects($this->once())
            ->method('getOwner')
            ->willReturn($owner);

        $this->setExpectedFormValues(['contexts' => [$context]]);

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with(
                $this->entity,
                [$context, $owner]
            );

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->handler->process($this->entity);

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    public function testProcessPutWithNotifyAttendeesAllWorks(): void
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::ALL_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesAllWorksAndDisabledInvitations(): void
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager->expects($this->never())
            ->method('onUpdate');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(false);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesAddedOrDeletedWorks(): void
    {
        $this->request->setMethod('PUT');

        ReflectionUtil::setId($this->entity, 123);
        $this->entity->addAttendee(new Attendee());

        $this->setExpectedFormValues(
            [
                'notifyAttendees' => NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY
            ]
        );

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::ADDED_OR_DELETED_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesNoneWorks(): void
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues([
            'notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY
        ]);

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPutWithNotifyAttendeesNotPassedWorks(): void
    {
        ReflectionUtil::setId($this->entity, 123);
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues([]);

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager->expects($this->once())
            ->method('onUpdate')
            ->with($this->entity, clone $this->entity, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyAttendeesNoneWorks(): void
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager->expects($this->once())
            ->method('onCreate')
            ->with($this->entity, NotificationManager::NONE_NOTIFICATIONS_STRATEGY);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(true);

        $this->handler->process($this->entity);
    }

    public function testProcessPostWithNotifyAttendeesNoneWorksAndDisabledInvitations(): void
    {
        $this->request->setMethod('POST');

        $this->setExpectedFormValues(['notifyAttendees' => NotificationManager::NONE_NOTIFICATIONS_STRATEGY]);

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, false);

        $this->notificationManager->expects($this->never())
            ->method('onCreate');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('calendar_events_attendee_notifications')
            ->willReturn(false);

        $this->handler->process($this->entity);
    }

    public function testProcessWithClearingExceptions(): void
    {
        $this->request->setMethod('PUT');

        $this->setExpectedFormValues(['updateExceptions' => true]);

        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);

        $this->handler->process($this->entity);
    }

    private function setExpectedFormValues(array $values): void
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
            $field = $this->createMock(FormInterface::class);
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
