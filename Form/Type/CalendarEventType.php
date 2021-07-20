<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventRecurrenceSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarUidSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorPickerType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderCollectionType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Form type for CalendarEvent entity
 */
class CalendarEventType extends AbstractType
{
    /** @var NotificationManager */
    protected $notificationManager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(
        NotificationManager $notificationManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        ManagerRegistry $registry
    ) {
        $this->notificationManager = $notificationManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->registry = $registry;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $minYear = date_create('-10 year')->format('Y');
        $maxYear = date_create('+80 year')->format('Y');
        $this->defineCalendar($builder);

        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.title.label'
                ]
            )
            ->add(
                'description',
                OroResizeableRichTextType::class,
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.description.label'
                ]
            )
            ->add(
                'start',
                OroDateTimeType::class,
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.start.label',
                    'attr'     => ['class' => 'start'],
                    'years'    => [$minYear, $maxYear],
                ]
            )
            ->add(
                'end',
                OroDateTimeType::class,
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.end.label',
                    'attr'     => ['class' => 'end'],
                    'years'    => [$minYear, $maxYear],
                ]
            )
            ->add(
                'allDay',
                CheckboxType::class,
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.all_day.label'
                ]
            )
            ->add(
                'backgroundColor',
                OroSimpleColorPickerType::class,
                [
                    'required'           => false,
                    'label'              => 'oro.calendar.calendarevent.background_color.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.calendarevent.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                ]
            )
            ->add(
                'reminders',
                ReminderCollectionType::class,
                [
                    'required' => false,
                    'label'    => 'oro.reminder.entity_plural_label'
                ]
            )
            ->add(
                'attendees',
                CalendarEventAttendeesSelectType::class,
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.attendees.label',
                    'layout_template' => $options['layout_template'],
                ]
            )
            ->add(
                'notifyAttendees',
                HiddenType::class,
                [
                    'mapped' => false,
                    'constraints' => [
                        new Choice(
                            [
                                'choices' => $this->notificationManager->getSupportedStrategies()
                            ]
                        )
                    ]
                ]
            )
            ->add(
                'recurrence',
                RecurrenceFormType::class,
                [
                    'required' => false,
                ]
            );

        $builder->addEventSubscriber(new CalendarUidSubscriber());
        $builder->addEventSubscriber(new CalendarEventRecurrenceSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_change_calendar' => false,
                'layout_template'       => false,
                'data_class'            => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'csrf_token_id'         => 'calendar_event',
                'csrf_protection'       => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getData() && $form->getData()->getRecurrence()) {
            /** @var FormView $childView */
            foreach ($view->children as $childView) {
                if ($childView->vars['name'] === 'reminders') {
                    $childView->vars['allow_add'] = false;
                    $childView->vars['allow_delete'] = false;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
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
        return 'oro_calendar_event';
    }

    protected function defineCalendar(FormBuilderInterface $builder)
    {
        if ($this->authorizationChecker->isGranted('oro_calendar_event_assign_management')) {
            $builder
                ->add(
                    'calendar',
                    UserSelectType::class,
                    [
                        'label' => 'oro.calendar.owner.label',
                        'required' => true,
                        'autocomplete_alias' => 'user_calendars',
                        'entity_class' => Calendar::class,
                        'configs' => array(
                            'entity_name' => Calendar::class,
                            'excludeCurrent' => true,
                            'component' => 'acl-user-autocomplete',
                            'permission' => 'VIEW',
                            'placeholder' => 'oro.calendar.form.choose_user_to_add_calendar',
                            'result_template_twig' => '@OroCalendar/Calendar/Autocomplete/result.html.twig',
                            'selection_template_twig' => '@OroCalendar/Calendar/Autocomplete/selection.html.twig',
                        ),

                        'grid_name' => 'users-calendar-select-grid-exclude-owner',
                        'random_id' => false
                    ]
                );
            $builder->addEventSubscriber(new CalendarSubscriber($this->tokenAccessor, $this->registry));
        }
    }
}
