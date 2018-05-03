<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurrenceFormType extends AbstractType
{
    /** @var Recurrence  */
    protected $recurrenceModel;

    /**
     * RecurrenceFormType constructor.
     *
     * @param Recurrence $recurrenceModel
     */
    public function __construct(Recurrence $recurrenceModel)
    {
        $this->recurrenceModel = $recurrenceModel;
    }

    /**
     * {@inheritdoc}
     * // TODO: remove SuppressWarnings with choices_as_values in scope of BAP-15236
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'recurrenceType',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.entity_label',
                    'placeholder' => false,
                    // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                    'choices_as_values' => true,
                    'choices' => $this->recurrenceModel->getRecurrenceTypes(),
                ]
            )
            ->add(
                'interval',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.interval.label',
                ]
            )
            ->add(
                'instance',
                ChoiceType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.instance.label',
                    'placeholder' => false,
                    // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                    'choices_as_values' => true,
                    'choices' => $this->recurrenceModel->getInstances(),
                ]
            )
            ->add(
                'dayOfWeek',
                ChoiceType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_week.label',
                    'multiple' => true,
                    // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                    'choices_as_values' => true,
                    'choices' => $this->recurrenceModel->getDaysOfWeek(),
                ]
            )
            ->add(
                'dayOfMonth',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_month.label',
                ]
            )
            ->add(
                'monthOfYear',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.month_of_year.label',
                ]
            )
            ->add(
                'startTime',
                DateTimeType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.start_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                ]
            )
            ->add(
                'endTime',
                DateTimeType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.end_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                ]
            )
            ->add(
                'occurrences',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.occurrences.label',
                    'property_path' => 'occurrences',
                ]
            )
            ->add(
                'timeZone',
                TimezoneType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.timezone.label',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_token_id' => 'oro_calendar_event_recurrence',
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\Recurrence',
            ]
        );
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
        return 'oro_calendar_event_recurrence';
    }
}
