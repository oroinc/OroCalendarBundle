<?php

namespace Oro\Bundle\CalendarBundle\Provider;

class PublicCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function applyItemPermissionsData(array &$item)
    {
        if (!$this->securityFacade->isGranted('oro_public_calendar_event_management')) {
            $item['editable']  = false;
            $item['removable'] = false;
        }
    }
}
