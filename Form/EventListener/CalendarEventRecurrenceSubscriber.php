<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

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
        $form = $event->getForm();
        $data = $event->getData();

        $isRecurrence = $form->has('recurrence') && empty($data['recurrence']);
        $isRepeat = $form->has('repeat') && empty($data['repeat']);
        if ($isRecurrence || $isRepeat) {
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
        $form = $event->getForm();
        $entity = $event->getData();
        if ($entity && $entity->getRecurringEvent() && $form->has('recurrence')) {
            $form->remove('recurrence');
        }
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();

        if ($entity && $entity->getRecurrence() && $form->has('repeat')) {
            $form->get('repeat')->setData(true);
        }
    }
}
