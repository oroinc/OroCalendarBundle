The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/master/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## Changes in the Calendar package versions

- [5.1.0](#510-2023-03-31)
- [5.0.0](#500-2022-01-26)
- [4.2.0](#420-2020-01-29)
- [4.1.0](#410-2020-01-31)
- [4.0.0](#400-2019-07-31)
- [3.1.0](#310-2019-01-30)
- [3.0.0](#300-2018-07-27)
- [2.5.0](#250-2017-11-30)
- [2.3.0](#230-2017-07-28)
- [2.1.0](#210-2017-03-30)

## 5.1.0 (2023-03-31)
[Show detailed list of changes](incompatibilities-5-1.md)

## 5.0.0 (2022-01-26)
[Show detailed list of changes](incompatibilities-5-0.md)

The link at the calendar events search items was changed,
  please reindex calendar event items with the `php bin/console oro:search:reindex --class="Oro\Bundle\CalendarBundle\Entity\CalendarEvent"` command.

### Changed

The link at the calendar events search items was changed from the link to events calendar to the link to event.

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

## 4.1.0 (2020-01-31)
[Show detailed list of changes](incompatibilities-4-1.md)

### Removed
* The `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g., `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).

* All `*.class` parameters for service definitions were removed from the dependency injection container.

## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

### Changed
* In the `Oro\Bundle\AuthorizeNetBundle\Controller\Frontend\PaymentProfileController::deleteAction` 
 (`oro_authorize_net_payment_profile_frontend_delete` route)
 action, the request method was changed to DELETE. 
* In the `Oro\Bundle\AuthorizeNetBundle\Controller\SettingsController::checkCredentialsAction` 
 (`oro_authorize_net_settings_check_credentials` route)
 action, the request method was changed to POST. 

## 3.1.0 (2019-01-30)
[Show detailed list of changes](incompatibilities-3-1.md)

## 3.0.0 (2018-07-27)
[Show detailed list of changes](incompatibilities-3-0.md)

## 2.5.0 (2017-11-30)
[Show detailed list of changes](incompatibilities-2-5.md)

## 2.3.0 (2017-07-28)
[Show detailed list of changes](incompatibilities-2-3.md)

### Changed
All existing classes were updated to use new services `security.authorization_checker`, `security.token_storage`, `oro_security.token_accessor`, `oro_security.class_authorization_checker`, `oro_security.request_authorization_checker` instead of `SecurityFacade` and `SecurityContext`.

## 2.1.0 (2017-03-30)
[Show detailed list of changes](incompatibilities-2-1.md)

### Changed
* The "Manage system calendar events" capability is merged with the "Manage system calendars" capability into one 
"Manage system calendars (and their events)" capability which is responsible for the system calendar and system calendar events 
ACL functionality.
* The "Manage system calendars (and their events)" capability is added. The "Manage organization calendar events" capability is 
merged into "Manage system calendars (and their events)" and is deleted. From now on, "Manage system calendars (and their 
events)" is responsible for the organization calendar and organization calendar events ACL functionality.

### Removed

* The `AjaxCalendarEventController::changeStatus`<sup>[[?]](https://github.com/oroinc/OroCalendarBundle/tree/2.0.0/Controller/AjaxCalendarEventController.php#L37 "Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatus")</sup> method is removed. Its logic is moved to `AjaxCalendarEventController::changeStatusAction`<sup>[[?]](https://github.com/oroinc/OroCalendarBundle/tree/2.1.0/Controller/AjaxCalendarEventController.php#L37 "Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatusAction")</sup>.
* The 'security' annotation for the Oro\Bundle\CalendarBundle\Entity\SystemCalendar entity is removed. So it is not ACL protected anymore.
* The following parameters is removed from DIC:
    - `oro_calendar.twig.dateformat.class`
    - `oro_calendar.twig.recurrence.class`
* The following services were marked as `private`:
    - `oro_calendar.twig.dateformat`
    - `oro_calendar.twig.recurrence`
