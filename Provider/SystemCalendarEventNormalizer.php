<?php

namespace Oro\Bundle\CalendarBundle\Provider;

/**
 * Applies permission-based restrictions to system calendar events based on user authorization.
 */
class SystemCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    #[\Override]
    protected function applyItemPermissionsData(array &$item)
    {
        if (!$this->authorizationChecker->isGranted('oro_system_calendar_event_management')) {
            $item['editable']  = false;
            $item['removable'] = false;
        }
    }
}
