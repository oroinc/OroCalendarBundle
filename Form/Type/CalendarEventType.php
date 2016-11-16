<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventRecurrenceSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarUidSubscriber;
use Oro\Bundle\CalendarBundle\Form\EventListener\ChildEventsSubscriber;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;

class CalendarEventType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var CalendarEventManager */
    protected $calendarEventManager;

    /** @var AttendeeManager */
    protected $attendeeManager;

    /**
     * CalendarEventType constructor.
     *
     * @param ManagerRegistry $registry
     * @param CalendarEventManager $calendarEventManager
     * @param AttendeeManager $attendeeManager
     */
    public function __construct(
        ManagerRegistry $registry,
        CalendarEventManager $calendarEventManager,
        AttendeeManager $attendeeManager
    ) {
        $this->registry = $registry;
        $this->calendarEventManager = $calendarEventManager;
        $this->attendeeManager = $attendeeManager;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'title',
                'text',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.title.label'
                ]
            )
            ->add(
                'description',
                'oro_resizeable_rich_text',
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.description.label'
                ]
            )
            ->add(
                'start',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.start.label',
                    'attr'     => ['class' => 'start'],
                ]
            )
            ->add(
                'end',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.end.label',
                    'attr'     => ['class' => 'end'],
                ]
            )
            ->add(
                'allDay',
                'checkbox',
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.all_day.label'
                ]
            )
            ->add(
                'backgroundColor',
                'oro_simple_color_picker',
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
                'oro_reminder_collection',
                [
                    'required' => false,
                    'label'    => 'oro.reminder.entity_plural_label'
                ]
            )
            ->add(
                'attendees',
                'oro_calendar_event_attendees_select',
                [
                    'required' => false,
                    'label'    => 'oro.calendar.calendarevent.attendees.label',
                    'layout_template' => $options['layout_template'],
                ]
            )
            ->add(
                'notifyInvitedUsers',
                'hidden',
                [
                    'mapped' => false
                ]
            )
            ->add(
                'repeat',
                'checkbox',
                [
                    'required' => false,
                    'mapped' => false
                ]
            )
            ->add(
                'recurrence',
                'oro_calendar_event_recurrence',
                [
                    'required' => false,
                    'attr' => ['data-validation-ignore' => '']
                ]
            );

        $builder->addEventSubscriber(new CalendarUidSubscriber());
        $builder->addEventSubscriber(new CalendarEventRecurrenceSubscriber($this->calendarEventManager));
        $builder->addEventSubscriber(new ChildEventsSubscriber(
            $this->registry,
            $this->calendarEventManager,
            $this->attendeeManager
        ));
    }

    /**
     *M-BM- {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_change_calendar' => false,
                'layout_template'       => false,
                'data_class'            => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'intention'             => 'calendar_event',
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
     *M-BM- {@inheritdoc}
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
}
