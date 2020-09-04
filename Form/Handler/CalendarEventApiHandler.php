<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;

/**
 * Form handler for calendar event form, used in legacy REST API.
 */
class CalendarEventApiHandler extends AbstractCalendarEventHandler
{
    /**
     * Process form
     *
     * @param  CalendarEvent $entity
     * @return bool  True on successful processing, false otherwise
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(CalendarEvent $entity)
    {
        $request = $this->getRequest();

        $this->form->setData($entity);

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            // clone entity to have original values later
            $originalEntity = clone $entity;

            $this->form->submit($request->request->all());

            if ($this->form->isValid()) {
                // Contexts handling should be moved to common for activities form handler
                if ($this->form->has('contexts') && $request->request->has('contexts')) {
                    $contexts = $this->form->get('contexts')->getData();
                    $owner = $entity->getCalendar() ? $entity->getCalendar()->getOwner() : null;
                    if ($owner && $owner->getId()) {
                        $contexts = array_merge($contexts, [$owner]);
                    }
                    $this->activityManager->setActivityTargets($entity, $contexts);
                } elseif (!$entity->getId() && $entity->getRecurringEvent()) {
                    $this->activityManager->setActivityTargets(
                        $entity,
                        $entity->getRecurringEvent()->getActivityTargets()
                    );
                }

                $this->onSuccess($entity, $originalEntity);

                return true;
            }
        }

        return false;
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
        return $this->form->has('updateExceptions') && $this->form->get('updateExceptions')->getData();
    }
}
