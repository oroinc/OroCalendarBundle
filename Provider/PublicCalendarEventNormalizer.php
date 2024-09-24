<?php

namespace Oro\Bundle\CalendarBundle\Provider;

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
