<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class CalendarEventApiHandler extends AbstractCalendarEventHandler
{
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
                        $entity->getRecurringEvent()->getActivityTargets()
                    );
                }

                $this->onSuccess($entity, $originaEntity);

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function allowSendNotifications()
    {
        return $this->form->has('notifyInvitedUsers') && $this->form->get('notifyInvitedUsers')->getData();
    }

    /**
     * {@inheritdoc}
     */
    protected function allowUpdateExceptions()
    {
        return $this->form->has('updateExceptions') && $this->form->get('updateExceptions')->getData();
    }
}
