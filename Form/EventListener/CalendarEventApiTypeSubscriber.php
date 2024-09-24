<?php

namespace Oro\Bundle\CalendarBundle\Form\EventListener;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Used to fix submitted form data according to expected values
 */
class CalendarEventApiTypeSubscriber implements EventSubscriberInterface
{
    protected CalendarEventManager $calendarEventManager;

    /**
     * CalendarEventApiTypeSubscriber constructor.
     */
    public function __construct(CalendarEventManager $calendarEventManager)
    {
        $this->calendarEventManager = $calendarEventManager;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
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
            ['allDay', 'isCancelled']
        );

        if (isset($data['attendees']) && ($data['attendees'] === '')) {
            $data['attendees'] = null;
        }

        // `Updated At` field can not be changed from outside
        unset($data['updatedAt']);

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
