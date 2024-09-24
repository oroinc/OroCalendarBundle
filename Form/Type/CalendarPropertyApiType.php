<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for CalendarProperty entity.
 */
class CalendarPropertyApiType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, ['mapped' => false])
            ->add(
                'targetCalendar',
                EntityIdentifierType::class,
                [
                    'required' => true,
                    'class'    => Calendar::class,
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'           => 'Oro\Bundle\CalendarBundle\Entity\CalendarProperty',
                'csrf_protection'      => false,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_calendar_property_api';
    }
}
