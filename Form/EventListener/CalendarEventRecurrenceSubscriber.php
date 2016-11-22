<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;

class CalendarEventRecurrenceSubscriber implements EventSubscriberInterface
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * CalendarEventRecurrenceSubscriber constructor.
     *
     * @param CalendarEventManager $calendarEventManager
     */
    public function __construct(CalendarEventManager $calendarEventManager)
    {
        $this->calendarEventManager = $calendarEventManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT   => 'preSubmit',
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SET_DATA => 'postSetData',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $this->clearOldRecurrence($event);
    }

    /**
     * If "repeat" field is unchecked the old instance of recurrence should be removed.
     * Or iff "recurrence" form field is empty the old instance of recurrence should be removed.
     *
     * @param FormEvent $event
     */
    protected function clearOldRecurrence(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $isRecurrence = $form->has('recurrence') && empty($data['recurrence']);
        $isRepeatUnchecked = $form->has('repeat') && empty($data['repeat']);

        if ($isRecurrence || $isRepeatUnchecked) {
            $recurrence = $form->get('recurrence')->getData();
            if ($recurrence) {
                $this->calendarEventManager->removeRecurrence($recurrence);
                $form->get('recurrence')->setData(null);
            }
            unset($data['recurrence']);
            $event->setData($data);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $this->removeRecurrenceFormFieldForException($event);
    }

    /**
     * Removes recurrence field from the form if entity represents an exception of recurring event.
     *
     * Exception should not have its' own recurrence.
     *
     * @param FormEvent $event
     */
    protected function removeRecurrenceFormFieldForException(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();

        if ($entity instanceof CalendarEvent && $entity->getRecurringEvent() && $form->has('recurrence')) {
            $form->remove('recurrence');
        }
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        $this->enableRepeatFieldForRecurringEvent($event);
    }

    /**
     * When form is shown for recurring event "repeat" field is marked as enabled.
     *
     * @param FormEvent $event
     */
    protected function enableRepeatFieldForRecurringEvent(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();

        if ($entity instanceof CalendarEvent && $entity->getRecurrence() && $form->has('repeat')) {
            $form->get('repeat')->setData(true);
        }
    }
}
