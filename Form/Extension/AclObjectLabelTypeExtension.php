<?php

namespace Oro\Bundle\CalendarBundle\Form\Extension;

use Oro\Bundle\SecurityBundle\Form\Type\ObjectLabelType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class AclObjectLabelTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($view->vars['value']) && $view->vars['value'] === 'oro.calendar.systemcalendar.entity_label') {
            $view->vars['value'] = 'oro.calendar.organization_calendar';
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ObjectLabelType::class];
    }
}
