<?php

namespace Oro\Bundle\CalendarBundle\Form\Extension;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventApiType;

class CalendarEventApiTypeExtension extends CalendarEventTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CalendarEventApiType::class];
    }
}
