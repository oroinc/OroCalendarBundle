<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventHandler extends AbstractCalendarEventHandler
{
    /**
     * @var EntityRoutingHelper
     */
    protected $entityRoutingHelper;

    /**
     * @param EntityRoutingHelper $entityRoutingHelper
     */
    public function setEntityRoutingHelper(EntityRoutingHelper $entityRoutingHelper)
    {
        $this->entityRoutingHelper = $entityRoutingHelper;
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
     * @throws AccessDeniedException
     */
    protected function checkPermission(CalendarEvent $entity)
    {
        if ($entity->getParent() !== null) {
            throw new AccessDeniedException();
        }
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
     * {@inheritdoc}
     */
    protected function getSendNotificationsStrategy()
    {
        return NotificationManager::ALL_NOTIFICATIONS_STRATEGY;
    }

    /**
     * {@inheritdoc}
     */
    protected function allowUpdateExceptions()
    {
        return true;
    }
}
