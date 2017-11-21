<?php

namespace Oro\Bundle\CalendarBundle\Form\Extension;

class CalendarEventApiTypeExtension extends CalendarEventTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_calendar_event_api';
    }
}
