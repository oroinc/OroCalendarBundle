<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarEventApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EmailSendProcessor */
    protected $emailSendProcessor;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * @param FormInterface        $form
     * @param Request              $request
     * @param ManagerRegistry      $doctrine
     * @param SecurityFacade       $securityFacade
     * @param EmailSendProcessor   $emailSendProcessor
     * @param ActivityManager      $activityManager
     * @param CalendarEventManager $calendarEventManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade,
        EmailSendProcessor $emailSendProcessor,
        ActivityManager $activityManager,
        CalendarEventManager $calendarEventManager
    ) {
        $this->form                 = $form;
        $this->request              = $request;
        $this->doctrine             = $doctrine;
        $this->emailSendProcessor   = $emailSendProcessor;
        $this->activityManager      = $activityManager;
        $this->securityFacade       = $securityFacade;
        $this->calendarEventManager = $calendarEventManager;
    }

    /**
     * Process form
     *
     * @param  CalendarEvent $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(CalendarEvent $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            // clone entity to have original values later
            $originaEntity = clone $entity;

            $this->form->submit($this->request->request->all());

            if ($this->form->isValid()) {
                // TODO: should be refactored after finishing BAP-8722
                // Contexts handling should be moved to common for activities form handler
                if ($this->form->has('contexts') && $this->request->request->has('contexts')) {
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

                $this->onSuccess($entity, $originaEntity);

                return true;
            }
        }

        return false;
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
            $this->allowUpdateExceptions()
        );

        $new = $entity->getId() ? false : true;

        $entityManager = $this->getEntityManager();

        if ($entity->isCancelled()) {
            $event = $entity->getParent() ? : $entity;
            $childEvents = $event->getChildEvents();
            foreach ($childEvents as $childEvent) {
                $childEvent->setCancelled(true);
            }
        }

        $entityManager->persist($entity);

        $entityManager->flush();

        if ($this->shouldNotifyInvitedUsers()) {
            if ($new) {
                $this->emailSendProcessor->sendInviteNotification($entity);
            } else {
                $this->emailSendProcessor->sendUpdateParentEventNotification(
                    $entity,
                    $originalEntity->getAttendees(),
                    true
                );
            }
        }
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
     * If API request contains a property "updateExceptions" exceptions of recurring event are allowed to clear
     * and update if necessary.
     *
     * @return bool
     */
    protected function allowUpdateExceptions()
    {
        return $this->form->has('updateExceptions') && $this->form->get('updateExceptions')->getData();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
