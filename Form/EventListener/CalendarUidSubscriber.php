<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarChoiceTemplateType;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarChoiceType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CalendarUidSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    /**
     * PRE_SET_DATA event handler
     */
    public function preSetData(FormEvent $event)
    {
        $form   = $event->getForm();
        $config = $form->getConfig();

        if (!$config->getOption('allow_change_calendar')) {
            return;
        }

        if ($config->getOption('layout_template')) {
            $form->add(
                'calendarUid',
                CalendarChoiceTemplateType::class,
                [
                    'required' => false,
                    'mapped'   => false,
                    'label'    => 'oro.calendar.calendarevent.calendar.label'
                ]
            );
        } else {
            /** @var CalendarEvent $data */
            $data = $event->getData();
            $form->add(
                $form->getConfig()->getFormFactory()->createNamed(
                    'calendarUid',
                    CalendarChoiceType::class,
                    $data ? $data->getCalendarUid() : null,
                    [
                        'required'        => false,
                        'mapped'          => false,
                        'auto_initialize' => false,
                        'is_new'          => !$data || !$data->getId(),
                        'label'           => 'oro.calendar.calendarevent.calendar.label'
                    ]
                )
            );
        }
    }
}
