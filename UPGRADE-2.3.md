UPGRADE FROM 2.2 to 2.3
=======================

**IMPORTANT**
-------------

The class `Oro\Bundle\SecurityBundle\SecurityFacade`, services `oro_security.security_facade` and `oro_security.security_facade.link`, and TWIG function `resource_granted` were marked as deprecated.
Use services `security.authorization_checker`, `security.token_storage`, `oro_security.token_accessor`, `oro_security.class_authorization_checker`, `oro_security.request_authorization_checker` and TWIG function `is_granted` instead.
In controllers use `isGranted` method from `Symfony\Bundle\FrameworkBundle\Controller\Controller`.
The usage of deprecated service `security.context` (interface `Symfony\Component\Security\Core\SecurityContextInterface`) was removed as well.
All existing classes were updated to use new services instead of the `SecurityFacade` and `SecurityContext`:

- service `security.authorization_checker`
    - implements `Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface`
    - the property name in classes that use this service is `authorizationChecker`
- service `security.token_storage`
    - implements `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface`
    - the property name in classes that use this service is `tokenStorage`
- service `oro_security.token_accessor`
    - implements `Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface`
    - the property name in classes that use this service is `tokenAccessor`
- service `oro_security.class_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker`
    - the property name in classes that use this service is `classAuthorizationChecker`
- service `oro_security.request_authorization_checker`
    - implements `Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker`
    - the property name in classes that use this service is `requestAuthorizationChecker`

Other changes
-------------

- Class `Oro\Bundle\CalendarBundle\Controller\SystemCalendarController`
    - removed method `getSecurityFacade`
- Class `Oro\Bundle\CalendarBundle\Controller\SystemCalendarEventController`
    - removed method `getSecurityFacade`
- Class `Oro\Bundle\CalendarBundle\EventListener\EntityListener`
    - removed method `getOrganization`
- Class `Oro\Bundle\CalendarBundle\Handler\CalendarEventDeleteHandler`
    - method `setSecurityFacade` was replaced with `setAuthorizationChecker`
- Class `Oro\Bundle\CalendarBundle\Handler\SystemCalendarDeleteHandler`
    - method `setSecurityFacade` was replaced with `setAuthorizationChecker`
- Class `Oro\Bundle\CalendarBundle\Provider\UserCalendarEventNormalizer`
    - added method `setTokenAccessor`
