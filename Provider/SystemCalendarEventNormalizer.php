<?php

namespace Oro\Bundle\CalendarBundle\Provider;

class SystemCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function applyItemPermissionsData(array &$item)
    {
        if (!$this->authorizationChecker->isGranted('oro_system_calendar_event_management')) {
            $item['editable']  = false;
            $item['removable'] = false;
        }
    }
}
