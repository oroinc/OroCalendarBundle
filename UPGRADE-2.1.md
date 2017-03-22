UPGRADE FROM 2.0 to 2.1
========================

####General
- Changed minimum required php version to 7.0
- Updated dependency to [fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) composer plugin to version 1.3.
- Composer updated to version 1.4.

```
    composer self-update
    composer global require "fxp/composer-asset-plugin"
```


#ACL changes
- Removed 'security' annotation for the entity Oro\Bundle\CalendarBundle\Entity\SystemCalendar entity. So it is not ACL 
protected anymore.
- "Manage system calendar events" capability merged with "Manage system calendars" capability into one 
"Manage system calendars (and their events)" capability which responsible for system calendar and system calendar events 
ACL functionality.
- Added "Manage system calendars (and their events)" capability. "Manage organization calendar events" capability 
merged into "Manage system calendars (and their events)" and no more exists. Now "Manage system calendars (and their 
events)" is responsible for organization calendar and organization calendar events ACL functionality.

- Removed the following parameters from DIC:
    - `oro_calendar.twig.dateformat.class`
    - `oro_calendar.twig.recurrence.class`
- The following services were marked as `private`:
    - `oro_calendar.twig.dateformat`
    - `oro_calendar.twig.recurrence`
- Class `Oro\Bundle\CalendarBundle\Twig\DateFormatExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
    - removed property `protected $configManager`
- Class `Oro\Bundle\CalendarBundle\Twig\RecurrenceExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $translator`
    - removed property `protected $model`
    
#Other changes
- Renamed method `Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatus` to `Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatusAction`.
