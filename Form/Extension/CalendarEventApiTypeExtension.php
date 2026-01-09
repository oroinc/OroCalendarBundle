<?php

namespace Oro\Bundle\CalendarBundle\Form\Extension;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventApiType;

/**
 * Form type extension for calendar event API forms.
 *
 * Applies calendar event customizations to the {@see CalendarEventApiType} form type.
 */
class CalendarEventApiTypeExtension extends CalendarEventTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [CalendarEventApiType::class];
    }
}
