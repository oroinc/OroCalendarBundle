<?php

namespace Oro\Bundle\CalendarBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * Form type for the calendar choice selection field; specifically designed for use in layout templates.
 */
class CalendarChoiceTemplateType extends AbstractType
{
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_calendar_choice_template';
    }
}
