<?php

namespace Oro\Bundle\CalendarBundle\Provider;

/**
 * Applies permission-based restrictions to public calendar events based on user authorization.
 */
class PublicCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    #[\Override]
    protected function applyItemPermissionsData(array &$item)
    {
        if (!$this->authorizationChecker->isGranted('oro_public_calendar_management')) {
            $item['editable']  = false;
            $item['removable'] = false;
        }
    }
}
