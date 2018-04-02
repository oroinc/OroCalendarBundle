<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarPropertyApiType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, ['mapped' => false])
            ->add(
                'targetCalendar',
                EntityIdentifierType::class,
                [
                    'required' => true,
                    'class'    => 'OroCalendarBundle:Calendar',
                    'multiple' => false
                ]
            )
            ->add('calendarAlias', TextType::class, ['required' => true])
            ->add('calendar', IntegerType::class, ['required' => true])
            ->add('position', IntegerType::class, ['required' => false])
            ->add('visible', CheckboxType::class, ['required' => false])
            ->add('backgroundColor', TextType::class, ['required' => false]);

        $builder->addEventSubscriber(new PatchSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\CalendarBundle\Entity\CalendarProperty',
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
        return 'oro_calendar_property_api';
    }
}
