<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Form\EventListener\AttendeesSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventApiTypeSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventRecurrenceSubscriber;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class CalendarEventApiType extends AbstractType
{
    /**
     * @var CalendarEventManager
     */
    protected $calendarEventManager;

    /**
     * @var NotificationManager
     */
    protected $notificationManager;

    /**
     * @param CalendarEventManager $calendarEventManager
     * @param NotificationManager  $notificationManager
     */
    public function __construct(CalendarEventManager $calendarEventManager, NotificationManager $notificationManager)
    {
        $this->calendarEventManager = $calendarEventManager;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'hidden', ['mapped' => false])
            ->add('uid', TextType::class, ['required' => false])
            ->add(
                'calendar',
                'integer',
                [
                    'required' => false,
                    'mapped'   => false,
                ]
            )
            ->add(
                'calendarAlias',
                'text',
                [
                    'required' => false,
                    'mapped'   => false,
                ]
            )
            ->add('title', 'text', ['required' => true])
            ->add('description', 'text', ['required' => false])
            ->add(
                'start',
                'datetime',
                [
                    'required'       => true,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'end',
                'datetime',
                [
                    'required'       => true,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add('allDay', 'checkbox', ['required' => false])
            ->add('backgroundColor', 'text', ['required' => false])
            ->add('reminders', 'oro_reminder_collection', ['required' => false])
            ->add(
                $builder->create(
                    'attendees',
                    'oro_collection',
                    [
                        'property_path' => 'attendees',
                        'type' => 'oro_calendar_event_attendees_api',
                        'error_bubbling' => false,
                        'options' => [
                            'required' => false,
                            'label'    => 'oro.calendar.calendarevent.attendees.label',
                        ],
                    ]
                )
                ->addEventSubscriber(new AttendeesSubscriber())
            )
            ->add(
                'notifyAttendees',
                'hidden',
                [
                    'mapped'         => false,
                    'error_bubbling' => false,
                    'constraints'    => [
                        new Choice(
                            [
                                'choices' => $this->notificationManager->getSupportedStrategies()
                            ]
                        )
                    ]
                ]
            )
            ->add(
                'createdAt',
                'datetime',
                [
                    'required'       => false,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'recurrence',
                'oro_calendar_event_recurrence',
                [
                    'required' => false,
                ]
            )
            ->add(
                'recurringEventId',
                'oro_entity_identifier',
                [
                    'required'      => false,
                    'property_path' => 'recurringEvent',
                    'class'         => 'OroCalendarBundle:CalendarEvent',
                    'multiple'      => false,
                ]
            )
            ->add(
                'originalStart',
                'datetime',
                [
                    'required'       => false,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add(
                'isCancelled',
                'checkbox',
                [
                    'required' => false,
                    'property_path' => 'cancelled',
                ]
            )
            ->add(
                'updateExceptions',
                'checkbox',
                [
                    'required' => false,
                    'mapped' => false,
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
        $builder->addEventSubscriber(new CalendarEventRecurrenceSubscriber());
        $builder->addEventSubscriber(new CalendarEventApiTypeSubscriber($this->calendarEventManager));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'intention'            => 'calendar_event',
                'csrf_protection'      => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_calendar_event_api';
    }
}
