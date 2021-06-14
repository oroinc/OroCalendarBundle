The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## 4.2.1

- The link at the calendar events search items was changed,
  please reindex calendar event items with command
  `php bin/console oro:search:reindex --class="Oro\Bundle\CalendarBundle\Entity\CalendarEvent"`

### Changed

The link at the calendar events search items was changed from the link to events calendar to the link to event.

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

## 4.1.0 (2020-01-31)
[Show detailed list of changes](incompatibilities-4-1.md)

### Removed
* `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g. `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).

## 4.1.0-rc (2019-12-10)
[Show detailed list of changes](incompatibilities-4-1-rc.md)

## 4.1.0-beta (2019-09-30)
[Show detailed list of changes](incompatibilities-4-1-beta.md)

### Removed
* All `*.class` parameters for service definitions were removed from the dependency injection container.

## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

## 4.0.0-rc (2019-05-29)
[Show detailed list of changes](incompatibilities-4-0-rc.md)

## 4.0.0-beta (2019-03-28)
[Show detailed list of changes](incompatibilities-4-0-beta.md)

### Changed
* In `Oro\Bundle\AuthorizeNetBundle\Controller\Frontend\PaymentProfileController::deleteAction` 
 (`oro_authorize_net_payment_profile_frontend_delete` route)
 action the request method was changed to DELETE. 
* In `Oro\Bundle\AuthorizeNetBundle\Controller\SettingsController::checkCredentialsAction` 
 (`oro_authorize_net_settings_check_credentials` route)
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
