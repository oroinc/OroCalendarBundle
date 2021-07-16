<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CalendarEventRecurrenceSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT   => 'preSubmit',
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSubmit(FormEvent $event)
    {
        $this->clearOldRecurrence($event);
    }

    /**
     * If "recurrence" form field is empty the old instance of recurrence should be removed.
     */
    protected function clearOldRecurrence(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $isRecurrence = $form->has('recurrence') && empty($data['recurrence']);

        if ($isRecurrence) {
            $recurrence = $form->get('recurrence')->getData();
            if ($recurrence) {
                $form->get('recurrence')->setData(null);
            }
            unset($data['recurrence']);
            $event->setData($data);
        }
    }

    public function preSetData(FormEvent $event)
    {
        $this->removeRecurrenceFormFieldForException($event);
    }

    /**
     * Removes recurrence field from the form if entity represents an exception of recurring event.
     *
     * Exception should not have its' own recurrence.
     */
    protected function removeRecurrenceFormFieldForException(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();

        if ($entity instanceof CalendarEvent && $entity->getRecurringEvent() && $form->has('recurrence')) {
            $form->remove('recurrence');
        }
    }
}
