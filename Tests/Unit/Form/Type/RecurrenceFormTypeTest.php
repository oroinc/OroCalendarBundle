<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\Recurrence as RecurrenceEntity;
use Oro\Bundle\CalendarBundle\Form\Type\RecurrenceFormType;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurrenceFormTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Recurrence */
    private $model;

    /** @var RecurrenceFormType */
    private $type;

    protected function setUp(): void
    {
        $this->model = new Recurrence($this->createMock(StrategyInterface::class));

        $this->type = new RecurrenceFormType($this->model);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);

        $formFields = [
            [
                'recurrenceType',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.entity_label',
                    'placeholder' => false,
                    'choices' => $this->model->getRecurrenceTypes(),
                ]
            ],
            [
                'interval',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.interval.label',
                ]
            ],
            [
                'instance',
                ChoiceType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.instance.label',
                    'placeholder' => false,
                    'choices' => $this->model->getInstances(),
                ]
            ],
            [
                'dayOfWeek',
                ChoiceType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_week.label',
                    'multiple' => true,
                    'choices' => $this->model->getDaysOfWeek(),
                ]
            ],
            [
                'dayOfMonth',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_month.label',
                ]
            ],
            [
                'monthOfYear',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.month_of_year.label',
                ]
            ],
            [
                'startTime',
                OroDateTimeType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.start_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text'
                ]
            ],
            [
                'endTime',
                OroDateTimeType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.end_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text'
                ]
            ],
            [
                'occurrences',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.occurrences.label',
                    'property_path' => 'occurrences',
                ]
            ],
            [
                'timeZone',
                TimezoneType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.timezone.label',
                ]
            ],
        ];

        $builder->expects($this->exactly(count($formFields)))
            ->method('add')
            ->withConsecutive(...$formFields)
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'csrf_token_id' => 'oro_calendar_event_recurrence',
                'data_class' => RecurrenceEntity::class
            ]);

        $this->type->configureOptions($resolver);
    }
}
