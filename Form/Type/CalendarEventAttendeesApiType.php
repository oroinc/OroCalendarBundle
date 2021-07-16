<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Attendees form of calendar event
 */
class CalendarEventAttendeesApiType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('displayName')
            ->add('email')
            ->add('status', null, ['dynamic_fields_ignore_exception' => true])
            ->add('type', null, ['dynamic_fields_ignore_exception' => true]);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [$this, 'preSubmit']
        );
    }

    /**
     * If attendee type is not supported set null.
     * If attendee type is not passed set "required".
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (!array_key_exists('type', $data)) {
            $data['type'] = Attendee::TYPE_REQUIRED;
            $event->setData($data);
        } elseif (!$this->isTypeSupported($data['type'])) {
            $data['type'] = null;
            $event->setData($data);
        }
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function isTypeSupported($type)
    {
        return in_array($type, [Attendee::TYPE_OPTIONAL, Attendee::TYPE_REQUIRED, Attendee::TYPE_ORGANIZER]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\CalendarBundle\Entity\Attendee',
            'error_bubbling' => false,
        ]);
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
        return 'oro_calendar_event_attendees_api';
    }
}
