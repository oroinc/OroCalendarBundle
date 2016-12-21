<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurrenceExtension extends \Twig_Extension
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var Recurrence */
    protected $model;

    /** @var array */
    protected $patternsCache = [];

    /**
     * RecurrenceExtension constructor.
     *
     * @param TranslatorInterface $translator
     * @param Recurrence $model
     */
    public function __construct(
        TranslatorInterface $translator,
        Recurrence $model
    ) {
        $this->translator = $translator;
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'get_recurrence_text_value' => new \Twig_Function_Method(
                $this,
                'getRecurrenceTextValue'
            ),
            'get_event_recurrence_pattern' => new \Twig_Function_Method(
                $this,
                'getEventRecurrencePattern'
            )
        ];
    }

    /**
     * Returns text representation of Recurrence object.
     *
     * @param null|Entity\Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getRecurrenceTextValue(Entity\Recurrence $recurrence = null)
    {
        $textValue = $this->translator->trans('oro.calendar.calendarevent.recurrence.na');
        if ($recurrence) {
            $textValue = $this->model->getTextValue($recurrence);
        }

        return $textValue;
    }

    /**
     * This method aimed to show recurrence text representation of events in email invitations.
     *
     * @param Entity\CalendarEvent $event
     *
     * @return string
     */
    public function getEventRecurrencePattern(Entity\CalendarEvent $event)
    {
        if (!isset($this->patternsCache[spl_object_hash($event)])) {
            $text = '';
            if ($event->getRecurrence()) {
                $text = $this->model->getTextValue($event->getRecurrence());
            } elseif ($event->getParent() && $event->getParent()->getRecurrence()) {
                $text = $this->model->getTextValue($event->getParent()->getRecurrence());
            }
            $this->patternsCache[spl_object_hash($event)] = $text; //regular events and exceptions
        }

        return $this->patternsCache[spl_object_hash($event)];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_recurrence';
    }
}
