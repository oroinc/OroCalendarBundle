<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

abstract class AbstractCalendarEventHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NotificationManager */
    protected $notificationManager;

    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * @param Request               $request
     * @param ManagerRegistry       $doctrine
     * @param SecurityFacade        $securityFacade
     * @param ActivityManager      $activityManager
     * @param CalendarEventManager  $calendarEventManager
     * @param NotificationManager   $notificationManager
     */
    public function __construct(
        Request $request,
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade,
        ActivityManager $activityManager,
        CalendarEventManager $calendarEventManager,
        NotificationManager $notificationManager
    ) {
        $this->request              = $request;
        $this->doctrine             = $doctrine;
        $this->securityFacade       = $securityFacade;
        $this->activityManager      = $activityManager;
        $this->calendarEventManager = $calendarEventManager;
        $this->notificationManager  = $notificationManager;
    }

    /**
     * @param FormInterface $form
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
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

        $isNew = $entity->getId() ? false : true;
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        if ($this->allowSendNotifications()) {
            $this->sendNotifications($entity, $originalEntity, $isNew);
        }
    }

    /**
     * Returns TRUE if exceptions of recurring event are allowed to clear and update if necessary.
     *
     * @return bool
     */
    abstract protected function allowUpdateExceptions();

    /**
     * Sends notification for calendar event if this is required.
     *
     * @param CalendarEvent $entity
     * @param CalendarEvent $originalEntity
     * @param boolean $isNew
     */
    protected function sendNotifications(CalendarEvent $entity, CalendarEvent $originalEntity, $isNew)
    {
        if ($isNew) {
            $this->notificationManager->onCreate($entity);
        } else {
            $this->notificationManager->onUpdate(
                $entity,
                $originalEntity,
                true // @todo Fix hard-coded value in BAP-13079, BAP-13080
            );
        }
    }

    /**
     * Returns TRUE if notifications should be send.
     *
     * @return bool
     */
    abstract protected function allowSendNotifications();

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
