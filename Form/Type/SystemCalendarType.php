<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Form type for SystemCalendar entity
 */
class SystemCalendarType extends AbstractType
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        SystemCalendarConfig $calendarConfig
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->calendarConfig = $calendarConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'oro.calendar.systemcalendar.name.label'
                ]
            )
            ->add(
                'backgroundColor',
                OroSimpleColorPickerType::class,
                [
                    'required'           => false,
                    'label'              => 'oro.calendar.systemcalendar.background_color.label',
                    'color_schema'       => 'oro_calendar.calendar_colors',
                    'empty_value'        => 'oro.calendar.systemcalendar.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\SystemCalendar',
                'csrf_token_id'  => 'system_calendar',
            ]
        );
    }

    /**
     * PRE_SET_DATA event handler
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        if ($this->calendarConfig->isPublicCalendarEnabled() && $this->calendarConfig->isSystemCalendarEnabled()) {
            $options = [
                'required'    => false,
                'label'       => 'oro.calendar.systemcalendar.public.label',
                'placeholder' => false,
                'choices'     => [
                    'oro.calendar.systemcalendar.scope.organization' => false,
                    'oro.calendar.systemcalendar.scope.system' => true,
                ]
            ];
            /** @var SystemCalendar|null $data */
            $data = $event->getData();
            if ($data) {
                $isPublicGranted = $this->authorizationChecker->isGranted('oro_public_calendar_management');
                $isSystemGranted = $this->authorizationChecker->isGranted('oro_system_calendar_management');
                if (!$isPublicGranted || !$isSystemGranted) {
                    $options['attr']['readonly'] = true;
                    if (!$data->getId() && !$isSystemGranted) {
                        $options['data'] = true;
                    }
                    unset($options['choices'][$isSystemGranted]);
                }
            }
            $form->add('public', ChoiceType::class, $options);
        } elseif ($this->calendarConfig->isPublicCalendarEnabled()) {
            $form->add('public', HiddenType::class, ['data' => true]);
        } elseif ($this->calendarConfig->isSystemCalendarEnabled()) {
            $form->add('public', HiddenType::class, ['data' => false]);
        }
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
        return 'oro_system_calendar';
    }
}
