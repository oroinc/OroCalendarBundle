## 2.3.0 (2017-07-28)
[Show detailed list of changes](file-incompatibilities-2-3-0.md)

### Changed
All existing classes were updated to use new services instead of the `SecurityFacade` and `SecurityContext`:

* service `security.authorization_checker`
    - implements `Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface`
    - the property name in classes that use this service is `authorizationChecker`
* service `security.token_storage`
    - implements `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface`
    - the property name in classes that use this service is `tokenStorage`
* service `oro_security.token_accessor`
    - implements `Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface`
    - the property name in classes that use this service is `tokenAccessor`
* service `oro_security.class_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker`
    - the property name in classes that use this service is `classAuthorizationChecker`
* service `oro_security.request_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker`
    - the property name in classes that use this service is `requestAuthorizationChecker`

### Deprecated
* The class `Oro\Bundle\SecurityBundle\SecurityFacade`, services `oro_security.security_facade` and `oro_security.security_facade.link`, and TWIG function `resource_granted` were marked as deprecated. Use services `security.authorization_checker`, `security.token_storage`, `oro_security.token_accessor`, `oro_security.class_authorization_checker`, `oro_security.request_authorization_checker` and TWIG function `is_granted` instead. In controllers use `isGranted` method from `Symfony\Bundle\FrameworkBundle\Controller\Controller`.
### Removed
* The usage of deprecated service `security.context` (interface `Symfony\Component\Security\Core\SecurityContextInterface`) was removed.
## 2.1.0 (2017-03-30)
[Show detailed list of changes](file-incompatibilities-2-1-0.md)
### Changed
- "Manage system calendar events" capability merged with "Manage system calendars" capability into one 
"Manage system calendars (and their events)" capability which responsible for system calendar and system calendar events 
ACL functionality.
- Added "Manage system calendars (and their events)" capability. "Manage organization calendar events" capability 
merged into "Manage system calendars (and their events)" and no more exists. Now "Manage system calendars (and their 
events)" is responsible for organization calendar and organization calendar events ACL functionality.
### Removed
- Removed method `Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatus` and moved its logic to `Oro\Bundle\CalendarBundle\Controller\AjaxCalendarEventController::changeStatusAction`.
### Removed
- Removed 'security' annotation for the entity Oro\Bundle\CalendarBundle\Entity\SystemCalendar entity. So it is not ACL 
protected anymore.
- Removed the following parameters from DIC:
    - `oro_calendar.twig.dateformat.class`
    - `oro_calendar.twig.recurrence.class`
- The following services were marked as `private`:
    - `oro_calendar.twig.dateformat`
    - `oro_calendar.twig.recurrence`
