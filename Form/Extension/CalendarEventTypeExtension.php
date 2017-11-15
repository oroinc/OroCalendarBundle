<?php

namespace Oro\Bundle\CalendarBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Resolver\EventOrganizerResolver;

class CalendarEventTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EventOrganizerResolver
     */
    private $organizerResolver;

    /**
     * @param EventOrganizerResolver $organizerResolver
     */
    public function __construct(EventOrganizerResolver $organizerResolver)
    {
        $this->organizerResolver = $organizerResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('organizerDisplayName')
            ->add('organizerEmail', EmailType::class)
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'setDefaultOrganizer']);
    }

    /**
     * @param FormEvent $event
     */
    public function setDefaultOrganizer(FormEvent $event)
    {
        /** @var CalendarEvent $calendarEvent */
        $calendarEvent = $event->getData();
        $this->organizerResolver->updateOrganizerInfo($calendarEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_calendar_event';
    }
}
