<?php

namespace Oro\Bundle\CalendarBundle\Form\Extension;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventApiType;

class CalendarEventApiTypeExtension extends CalendarEventTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CalendarEventApiType::class;
    }
}
