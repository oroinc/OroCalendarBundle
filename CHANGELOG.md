Please refer first to [UPGRADE.md](UPGRADE.md) for the most important items that should be addressed before attempting to upgrade or during the upgrade of a vanilla Oro application.

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## 3.1.4
### Changed
* In `Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatusAction` 
 (`oro_calendar_event_accepted`, `oro_calendar_event_tentative` and `oro_calendar_event_declined` routes)
 action the request method was changed to POST.

## 3.1.0 (2019-01-30)
[Show detailed list of changes](incompatibilities-3-1.md)

## 3.0.0-beta (2018-03-30)
[Show detailed list of changes](incompatibilities-3-0-beta.md)

## 2.5.0 (2017-11-30)
[Show detailed list of changes](incompatibilities-2-5.md)

## 2.3.0 (2017-07-28)
[Show detailed list of changes](incompatibilities-2-3.md)

### Changed
All existing classes were updated to use new services `security.authorization_checker`, `security.token_storage`, `oro_security.token_accessor`, `oro_security.class_authorization_checker`, `oro_security.request_authorization_checker` instead of the `SecurityFacade` and `SecurityContext`.

## 2.1.0 (2017-03-30)
[Show detailed list of changes](incompatibilities-2-1.md)
### Changed
- "Manage system calendar events" capability merged with "Manage system calendars" capability into one 
"Manage system calendars (and their events)" capability which responsible for system calendar and system calendar events 
ACL functionality.
- Added "Manage system calendars (and their events)" capability. "Manage organization calendar events" capability 
merged into "Manage system calendars (and their events)" and no more exists. Now "Manage system calendars (and their 
events)" is responsible for organization calendar and organization calendar events ACL functionality.
### Removed
- Removed method `AjaxCalendarEventController::changeStatus`<sup>[[?]](https://github.com/oroinc/OroCalendarBundle/tree/2.0.0/Controller/AjaxCalendarEventController.php#L37 "Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatus")</sup> and moved its logic to `AjaxCalendarEventController::changeStatusAction`<sup>[[?]](https://github.com/oroinc/OroCalendarBundle/tree/2.1.0/Controller/AjaxCalendarEventController.php#L37 "Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatusAction")</sup>.
- Removed 'security' annotation for the entity Oro\Bundle\CalendarBundle\Entity\SystemCalendar entity. So it is not ACL 
protected anymore.
- Removed the following parameters from DIC:
    - `oro_calendar.twig.dateformat.class`
    - `oro_calendar.twig.recurrence.class`
- The following services were marked as `private`:
    - `oro_calendar.twig.dateformat`
    - `oro_calendar.twig.recurrence`
