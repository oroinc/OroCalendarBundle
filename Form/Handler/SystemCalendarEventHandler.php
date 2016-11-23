<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SystemCalendarEventHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * @param FormInterface        $form
     * @param Request              $request
     * @param ManagerRegistry      $doctrine
     * @param SecurityFacade       $securityFacade
     * @param ActivityManager      $activityManager
     * @param CalendarEventManager $calendarEventManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade,
        ActivityManager $activityManager,
        CalendarEventManager $calendarEventManager
    ) {
        $this->form                 = $form;
        $this->request              = $request;
        $this->doctrine             = $doctrine;
        $this->securityFacade       = $securityFacade;
        $this->activityManager      = $activityManager;
        $this->calendarEventManager = $calendarEventManager;
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
     * @param CalendarEvent $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(CalendarEvent $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                // TODO: should be refactored after finishing BAP-8722
                // Contexts handling should be moved to common for activities form handler
                if ($this->form->has('contexts')) {
                    $contexts = $this->form->get('contexts')->getData();
                    if ($entity->getCalendar()) {
                        $owner = $entity->getCalendar() ? $entity->getCalendar()->getOwner() : null;
                        if ($owner && $owner->getId()) {
                            $contexts = array_merge($contexts, [$owner]);
                        }
                    }
                    $this->activityManager->setActivityTargets($entity, $contexts);
                }

                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent $entity
     */
    protected function onSuccess(CalendarEvent $entity)
    {
        $this->calendarEventManager->onEventUpdate($entity, $this->securityFacade->getOrganization());

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
