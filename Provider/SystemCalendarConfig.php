<?php

namespace Oro\Bundle\CalendarBundle\Provider;

/**
 * Configuration provider for system and public calendar availability.
 *
 * Determines which calendar types (public/system/organization) are enabled
 * based on system configuration settings.
 */
class SystemCalendarConfig
{
    /** @var bool */
    protected $isPublicEnabled = false;

    /** @var bool */
    protected $isSystemEnabled = false;

    /**
     * @param string|boolean $enabledSystemCalendar
     */
    public function __construct($enabledSystemCalendar)
    {
        if ($enabledSystemCalendar === true) {
            $this->isPublicEnabled = true;
            $this->isSystemEnabled = true;
        } elseif ($enabledSystemCalendar === 'organization') {
            $this->isSystemEnabled = true;
        } elseif ($enabledSystemCalendar === 'system') {
            $this->isPublicEnabled = true;
        }
    }

    /**
     * Indicates whether system wide calendars are enabled or not
     *
     * @return bool
     */
    public function isPublicCalendarEnabled()
    {
        return $this->isPublicEnabled;
    }

    /**
     * Indicates whether organization calendars are enabled or not
     *
     * @return bool
     */
    public function isSystemCalendarEnabled()
    {
        return $this->isSystemEnabled;
    }
}
