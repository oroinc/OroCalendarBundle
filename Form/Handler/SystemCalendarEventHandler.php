<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;

class SystemCalendarEventHandler extends AbstractCalendarEventHandler
{
    use RequestHandlerTrait;

    /**
     * Process form
     *
     * @param CalendarEvent $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(CalendarEvent $entity)
    {
        $request = $this->getRequest();

        $this->form->setData($entity);

        if (in_array($request->getMethod(), array('POST', 'PUT'))) {
            $originalEntity = clone $entity;
            $this->submitPostPutRequest($this->form, $request);

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

                $this->onSuccess($entity, $originalEntity);

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function allowUpdateExceptions()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSendNotificationsStrategy()
    {
        return NotificationManager::NONE_NOTIFICATIONS_STRATEGY;
    }
}
