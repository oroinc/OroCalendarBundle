<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CalendarEventApiTypeSubscriber implements EventSubscriberInterface
{
    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /**
     * CalendarEventApiTypeSubscriber constructor.
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
            FormEvents::PRE_SUBMIT  => ['preSubmitData', 10],
            FormEvents::POST_SUBMIT  => ['postSubmitData', 10],
        ];
    }

    public function preSubmitData(FormEvent $formEvent)
    {
        $data = $formEvent->getData();

        $this->fixBooleanFields(
            $data,
            ['allDay', 'isCancelled', 'use_hangout']
        );

        if (isset($data['attendees']) && ($data['attendees'] === '')) {
            $data['attendees'] = null;
        }

        $formEvent->setData($data);
    }

    /**
     * Normalize boolean values of the form data.
     */
    protected function fixBooleanFields(array &$data, array $booleanFields)
    {
        foreach ($booleanFields as $name) {
            if (isset($data[$name])) {
                $value = $data[$name];
                if (is_string($value)) {
                    $value = strtolower($value);
                    $data[$name] = ($value === '1' || $value === 'true');
                } else {
                    $data[$name] = (bool)$value;
                }
            }
        }

        if (isset($data['attendees']) && ($data['attendees'] === '')) {
            $data['attendees'] = null;
        }
    }

    /**
     * POST_SUBMIT event handler
     */
    public function postSubmitData(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var CalendarEvent $data */
        $data = $form->getData();
        if (empty($data)) {
            return;
        }

        $calendarId = $form->get('calendar')->getData();
        if (empty($calendarId)) {
            return;
        }
        $calendarAlias = $form->get('calendarAlias')->getData();
        if (empty($calendarAlias)) {
            $calendarAlias = Calendar::CALENDAR_ALIAS;
        }

        $this->calendarEventManager->setCalendar($data, $calendarAlias, (int)$calendarId);
    }
}
