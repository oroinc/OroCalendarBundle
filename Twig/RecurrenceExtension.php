<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to display calendar event recurrence:
 *   - get_recurrence_text_value
 *   - get_event_recurrence_pattern
 */
class RecurrenceExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $patternsCache = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get(TranslatorInterface::class);
    }

    /**
     * @return Recurrence
     */
    protected function getRecurrenceModel()
    {
        return $this->container->get('oro_calendar.model.recurrence');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_recurrence_text_value', [$this, 'getRecurrenceTextValue']),
            new TwigFunction('get_event_recurrence_pattern', [$this, 'getEventRecurrencePattern'])
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
        $textValue = '';
        if ($recurrence) {
            $textValue = $this->getRecurrenceModel()->getTextValue($recurrence);
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
                $text = $this->getRecurrenceModel()->getTextValue($event->getRecurrence());
            } elseif ($event->getParent() && $event->getParent()->getRecurrence()) {
                $text = $this->getRecurrenceModel()->getTextValue($event->getParent()->getRecurrence());
            }
            $this->patternsCache[spl_object_hash($event)] = $text; //regular events and exceptions
        }

        return $this->patternsCache[spl_object_hash($event)];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            TranslatorInterface::class,
            'oro_calendar.model.recurrence' => Recurrence::class,
        ];
    }
}
