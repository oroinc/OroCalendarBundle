<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\CalendarBundle\Form\EventListener\AttendeesSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventApiTypeSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventRecurrenceSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderCollectionType;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Form type for Calendar Event
 */
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
            ->add('id', HiddenType::class, ['mapped' => false])
            ->add('uid', TextType::class, ['required' => false])
            ->add(
                'calendar',
                IntegerType::class,
                [
                    'required' => false,
                    'mapped'   => false,
                ]
            )
            ->add(
                'calendarAlias',
                TextType::class,
                [
                    'required' => false,
                    'mapped'   => false,
                ]
            )
            ->add('title', TextType::class, ['required' => true])
            ->add('description', TextType::class, ['required' => false])
            ->add(
                'start',
                OroDateTimeType::class,
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
                OroDateTimeType::class,
                [
                    'required'       => true,
                    'with_seconds'   => true,
                    'widget'         => 'single_text',
                    'format'         => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->add('allDay', CheckboxType::class, ['required' => false])
            ->add('backgroundColor', TextType::class, ['required' => false])
            ->add('reminders', ReminderCollectionType::class, ['required' => false])
            ->add(
                $builder->create(
                    'attendees',
                    CollectionType::class,
                    [
                        'property_path' => 'attendees',
                        'entry_type' => CalendarEventAttendeesApiType::class,
                        'error_bubbling' => false,
                        'entry_options' => [
                            'required' => false,
                            'label'    => 'oro.calendar.calendarevent.attendees.label',
                        ],
                    ]
                )
                ->addEventSubscriber(new AttendeesSubscriber())
            )
            ->add(
                'notifyAttendees',
                HiddenType::class,
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
                OroDateTimeType::class,
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
                RecurrenceFormType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'recurringEventId',
                EntityIdentifierType::class,
                [
                    'required'      => false,
                    'property_path' => 'recurringEvent',
                    'class'         => 'OroCalendarBundle:CalendarEvent',
                    'multiple'      => false,
                ]
            )
            ->add(
                'originalStart',
                OroDateTimeType::class,
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
                CheckboxType::class,
                [
                    'required' => false,
                    'property_path' => 'cancelled',
                ]
            )
            ->add(
                'updateExceptions',
                CheckboxType::class,
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
                'csrf_token_id'        => 'calendar_event',
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
