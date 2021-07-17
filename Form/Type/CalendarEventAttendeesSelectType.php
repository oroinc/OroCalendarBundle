<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\FormBundle\Form\Type\Select2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarEventAttendeesSelectType extends AbstractType
{
    /**
     * @var AttendeeManager
     */
    protected $attendeeManager;

    /**
     * @var DataTransformerInterface
     */
    protected $attendeesToViewTransformer;

    /**
     * @var AttendeeRelationManager
     */
    protected $attendeeRelationManager;

    public function __construct(
        DataTransformerInterface $attendeesToViewTransformer,
        AttendeeManager $attendeeManager,
        AttendeeRelationManager $attendeeRelationManager
    ) {
        $this->attendeesToViewTransformer = $attendeesToViewTransformer;
        $this->attendeeManager            = $attendeeManager;
        $this->attendeeRelationManager    = $attendeeRelationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        if ($this->attendeesToViewTransformer instanceof ContextsToViewTransformer) {
            $this->attendeesToViewTransformer->setSeparator($options['configs']['separator']);
        }
        $builder->addViewTransformer($this->attendeesToViewTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-selected-data'] = $this->getSelectedData($form, $options['configs']['separator']);
        if ($form->getData()) {
            $view->vars['configs']['selected'] = $this->attendeeManager->createAttendeeExclusions($form->getData());
        }
    }

    /**
     * @param FormInterface $form
     * @param string $separator
     * @return string
     */
    protected function getSelectedData(FormInterface $form, $separator)
    {
        $value = '';
        $attendees = $form->getData();
        if ($attendees) {
            $result = [];

            /**
             * @var Attendee $attendee
             */
            foreach ($attendees as $attendee) {
                $result[] = json_encode(
                    [
                        'text'        => $this->attendeeRelationManager->getDisplayName($attendee),
                        'displayName' => $attendee->getDisplayName(),
                        'email'       => $attendee->getEmail(),
                        'type'        => $attendee->getType() ? $attendee->getType()->getId() : null,
                        'status'      => $attendee->getStatus() ? $attendee->getStatus()->getId() : null,
                        'userId'      => $attendee->getUser() ? $attendee->getUser()->getId() : null,
                        /**
                         * Selected Value Id should additionally encoded because it should be used as string key
                         * to compare with value
                         */
                        'id'          => json_encode(
                            [
                                'entityClass' => Attendee::class,
                                'entityId'    => $attendee->getId(),
                            ]
                        )
                    ]
                );
            }

            $value = implode($separator, $result);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'tooltip' => false,
            'layout_template' => false,
            'configs' => function (Options $options, $value) {
                $configs = [
                    'placeholder'        => 'oro.user.form.choose_user',
                    'allowClear'         => true,
                    'multiple'           => true,
                    'separator'          => ContextsToViewTransformer::SEPARATOR,
                    'forceSelectedData'  => true,
                    'minimumInputLength' => 0,
                    'route_name'         => 'oro_calendarevent_autocomplete_attendees',
                    'component'          => 'attendees',
                    'needsInit'         => $options['layout_template'],
                    'route_parameters'   => [
                        'name' => 'name',
                    ],
                ];

                return $configs;
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2HiddenType::class;
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
        return 'oro_calendar_event_attendees_select';
    }
}
