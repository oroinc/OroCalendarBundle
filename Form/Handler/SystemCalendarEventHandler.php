<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class SystemCalendarEventHandler extends AbstractCalendarEventHandler
{
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
            $originalEntity = clone $entity;
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
    protected function allowSendNotifications()
    {
        return false;
    }
}
