<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NavigationListener
{
    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param SystemCalendarConfig $calendarConfig
     */
    public function __construct(SystemCalendarConfig $calendarConfig)
    {
        $this->calendarConfig = $calendarConfig;
    }

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        if ($this->tokenAccessor->hasUser()) {
            $isPublicGranted = $this->calendarConfig->isPublicCalendarEnabled()
                && $this->authorizationChecker->isGranted('oro_public_calendar_management');
            $isSystemGranted = $this->calendarConfig->isSystemCalendarEnabled()
                && $this->authorizationChecker->isGranted('oro_system_calendar_management');
        } else {
            $isPublicGranted = $this->calendarConfig->isPublicCalendarEnabled();
            $isSystemGranted = $this->calendarConfig->isSystemCalendarEnabled();
        }

        if (!$isPublicGranted && !$isSystemGranted) {
            $calendarListItem = MenuUpdateUtils::findMenuItem($event->getMenu(), 'oro_system_calendar_list');
            if ($calendarListItem !== null) {
                $calendarListItem->setDisplay(false);
            }
        }
    }
}
