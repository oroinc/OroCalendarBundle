<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Form\Type\RecurrenceFormType;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class RecurrenceFormTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var RecurrenceFormType */
    protected $type;

    /** @var  Recurrence */
    protected $model;

    protected function setUp(): void
    {
        $strategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface')
            ->getMock();

        $this->model = new Recurrence($strategy);
        $this->type = new RecurrenceFormType($this->model);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'recurrenceType',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.entity_label',
                    'placeholder' => false,
                    'choices' => $this->model->getRecurrenceTypes(),
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'interval',
                IntegerType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.interval.label',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'instance',
                ChoiceType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.instance.label',
                    'placeholder' => false,
                    'choices' => $this->model->getInstances(),
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'dayOfWeek',
                ChoiceType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_week.label',
                    'multiple' => true,
                    'choices' => $this->model->getDaysOfWeek(),
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'dayOfMonth',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_month.label',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'monthOfYear',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.month_of_year.label',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'startTime',
                OroDateTimeType::class,
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.start_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text'
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(7))
            ->method('add')
            ->with(
                'endTime',
                OroDateTimeType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.end_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text'
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(8))
            ->method('add')
            ->with(
                'occurrences',
                IntegerType::class,
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.occurrences.label',
                    'property_path' => 'occurrences',
                ]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'csrf_token_id' => 'oro_calendar_event_recurrence',
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\Recurrence',
            ]);

        $this->type->configureOptions($resolver);
    }
}
