<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EmailSendProcessor */
    protected $emailSendProcessor;

    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * CalendarEventHandler constructor.
     *
     * @param FormInterface $form
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @param ActivityManager $activityManager
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param SecurityFacade $securityFacade
     * @param EmailSendProcessor $emailSendProcessor
     * @param CalendarEventManager $calendarEventManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $doctrine,
        ActivityManager $activityManager,
        EntityRoutingHelper $entityRoutingHelper,
        SecurityFacade $securityFacade,
        EmailSendProcessor $emailSendProcessor,
        CalendarEventManager $calendarEventManager
    ) {
        $this->form                        = $form;
        $this->request                     = $request;
        $this->doctrine                    = $doctrine;
        $this->activityManager             = $activityManager;
        $this->entityRoutingHelper         = $entityRoutingHelper;
        $this->securityFacade              = $securityFacade;
        $this->emailSendProcessor          = $emailSendProcessor;
        $this->calendarEventManager        = $calendarEventManager;
    }

    /**
     * Get form, that build into handler, via handler service
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Process form
     *
     * @param  CalendarEvent $entity
     *
     * @return bool True on successful processing, false otherwise
     *
     * @throws AccessDeniedException
     * @throws \LogicException
     */
    public function process(CalendarEvent $entity)
    {
        $this->checkPermission($entity);

        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            // clone entity to have original values later
            $originalEntity = clone $entity;

            $this->ensureCalendarSet($entity);

            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                // TODO: should be refactored after finishing BAP-8722
                // Contexts handling should be moved to common for activities form handler
                if ($this->form->has('contexts')) {
                    $contexts = $this->form->get('contexts')->getData();
                    $owner = $entity->getCalendar() ? $entity->getCalendar()->getOwner() : null;
                    if ($owner && $owner->getId()) {
                        $contexts = array_merge($contexts, [$owner]);
                    }
                    $this->activityManager->setActivityTargets($entity, $contexts);
                } elseif (!$entity->getId() && $entity->getRecurringEvent()) {
                    $this->activityManager->setActivityTargets(
                        $entity,
                        $entity->getRecurringEvent()->getActivityTargetEntities()
                    );
                }

                $this->processTargetEntity($entity);

                $this->onSuccess($entity, $originalEntity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param CalendarEvent $entity
     *
     * @throws \LogicException
     */
    protected function ensureCalendarSet(CalendarEvent $entity)
    {
        if ($entity->getCalendar() || $entity->getSystemCalendar()) {
            return;
        }
        if (!$this->securityFacade->getLoggedUser() || !$this->securityFacade->getOrganization()) {
            throw new \LogicException('Both logged in user and organization must be defined.');
        }

        /** @var Calendar $defaultCalendar */
        $defaultCalendar = $this->getEntityManager()
            ->getRepository('OroCalendarBundle:Calendar')
            ->findDefaultCalendar(
                $this->securityFacade->getLoggedUser()->getId(),
                $this->securityFacade->getOrganization()->getId()
            );
        $entity->setCalendar($defaultCalendar);
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent $entity
     * @param CalendarEvent $originalEntity
     */
    protected function onSuccess(CalendarEvent $entity, CalendarEvent $originalEntity)
    {
        $this->calendarEventManager->onEventUpdate(
            $entity,
            $originalEntity,
            $this->securityFacade->getOrganization(),
            true
        );

        $new = $entity->getId() ? false : true;
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();


        if ($new) {
            $this->emailSendProcessor->sendInviteNotification($entity);
        } else {
            $this->emailSendProcessor->sendUpdateParentEventNotification(
                $entity,
                $originalEntity->getAttendees(),
                $this->shouldNotifyInvitedUsers()
            );
        }
    }

    /**
     * @param CalendarEvent $entity
     *
     * @throws AccessDeniedException
     */
    protected function checkPermission(CalendarEvent $entity)
    {
        if ($entity->getParent() !== null) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param $entity
     *
     * @return CalendarEventHandler
     */
    protected function processTargetEntity($entity)
    {
        $targetEntityClass = $this->entityRoutingHelper->getEntityClassName($this->request);
        if ($targetEntityClass) {
            $targetEntityId = $this->entityRoutingHelper->getEntityId($this->request);
            $targetEntity   = $this->entityRoutingHelper->getEntityReference(
                $targetEntityClass,
                $targetEntityId
            );

            $action = $this->entityRoutingHelper->getAction($this->request);
            if ($action === 'activity') {
                $this->activityManager->addActivityTarget($entity, $targetEntity);
            }

            if ($action === 'assign'
                && $targetEntity instanceof User
                && $targetEntityId !== $this->securityFacade->getLoggedUserId()
            ) {
                /** @var Calendar $defaultCalendar */
                $defaultCalendar = $this->getEntityManager()
                    ->getRepository('OroCalendarBundle:Calendar')
                    ->findDefaultCalendar($targetEntity->getId(), $targetEntity->getOrganization()->getId());
                $entity->setCalendar($defaultCalendar);
            }
        }

        return $this;
    }

    /**
     * If API request contains a property "notifyInvitedUsers" with TRUE value, notification should be send.
     *
     * @return bool
     */
    protected function shouldNotifyInvitedUsers()
    {
        return $this->form->has('notifyInvitedUsers') && $this->form->get('notifyInvitedUsers')->getData();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
