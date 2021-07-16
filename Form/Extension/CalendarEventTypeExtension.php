<?php

namespace Oro\Bundle\CalendarBundle\Form\Extension;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;
use Oro\Bundle\CalendarBundle\Resolver\EventOrganizerResolver;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds organizer field to calendar event form.
 */
class CalendarEventTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EventOrganizerResolver
     */
    private $organizerResolver;

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
            ->add(
                'organizerDisplayName',
                TextType::class,
                [
                    'label' => 'oro.calendar.calendarevent.organizer_display_name.label'
                ]
            )
            ->add(
                'organizerEmail',
                EmailType::class,
                [
                    'label' => 'oro.calendar.calendarevent.organizer_email.label'
                ]
            )
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'setDefaultOrganizer']);
    }

    public function setDefaultOrganizer(FormEvent $event)
    {
        /** @var CalendarEvent $calendarEvent */
        $calendarEvent = $event->getData();
        $this->organizerResolver->updateOrganizerInfo($calendarEvent);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CalendarEventType::class];
    }
}
