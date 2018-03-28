<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CalendarEventHandler extends AbstractCalendarEventHandler
{
    use RequestHandlerTrait;

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
        $request = $this->getRequest();

        $this->checkPermission($entity);

        $this->form->setData($entity);

        if (in_array($request->getMethod(), array('POST', 'PUT'))) {
            // clone entity to have original values later
            $originalEntity = clone $entity;

            $this->ensureCalendarSet($entity);

            $this->submitPostPutRequest($this->form, $request);

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

                $this->processTargetEntity($entity, $request);

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

        $userId = $this->tokenAccessor->getUserId();
        $organizationId = $this->tokenAccessor->getOrganizationId();
        if (null === $userId || null === $organizationId) {
            throw new \LogicException('Both logged in user and organization must be defined.');
        }

        /** @var Calendar $defaultCalendar */
        $defaultCalendar = $this->getEntityManager()
            ->getRepository('OroCalendarBundle:Calendar')
            ->findDefaultCalendar($userId, $organizationId);
        $entity->setCalendar($defaultCalendar);
    }

    /**
     * @param         $entity
     * @param Request $request
     *
     * @return CalendarEventHandler
     */
    protected function processTargetEntity($entity, Request $request)
    {
        $targetEntityClass = $this->entityRoutingHelper->getEntityClassName($request);
        if ($targetEntityClass) {
            $targetEntityId = $this->entityRoutingHelper->getEntityId($request);
            $targetEntity   = $this->entityRoutingHelper->getEntityReference(
                $targetEntityClass,
                $targetEntityId
            );

            $action = $this->entityRoutingHelper->getAction($request);
            if ($action === 'activity') {
                $this->activityManager->addActivityTarget($entity, $targetEntity);
            }

            if ($action === 'assign'
                && $targetEntity instanceof User
                && $targetEntityId !== $this->tokenAccessor->getUserId()
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
        if ($this->form->has('notifyAttendees') && $this->form->get('notifyAttendees')->getData()) {
            return $this->form->get('notifyAttendees')->getData();
        }

        return NotificationManager::NONE_NOTIFICATIONS_STRATEGY;
    }

    /**
     * {@inheritdoc}
     */
    protected function allowUpdateExceptions()
    {
        return true;
    }
}
