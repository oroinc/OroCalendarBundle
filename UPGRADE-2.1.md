UPGRADE FROM 2.0 to 2.1
=======================

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