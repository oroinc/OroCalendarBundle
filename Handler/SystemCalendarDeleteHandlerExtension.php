<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The delete handler extension for SystemCalendar entity.
 */
class SystemCalendarDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /** @var SystemCalendarConfig */
    private $calendarConfig;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    public function __construct(
        SystemCalendarConfig $calendarConfig,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->calendarConfig = $calendarConfig;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var SystemCalendar $entity */

        if ($entity->isPublic()) {
            if (!$this->calendarConfig->isPublicCalendarEnabled()) {
                throw $this->createAccessDeniedException('public calendars are disabled');
            }
            if (!$this->authorizationChecker->isGranted('oro_public_calendar_management')) {
                throw $this->createAccessDeniedException();
            }
        } else {
            if (!$this->calendarConfig->isSystemCalendarEnabled()) {
                throw $this->createAccessDeniedException('system calendars are disabled');
            }
            if (!$this->authorizationChecker->isGranted('oro_system_calendar_management')) {
                throw $this->createAccessDeniedException();
            }
            $organization = $entity->getOrganization();
            if (null !== $organization && $organization->getId() !== $this->tokenAccessor->getOrganizationId()) {
                throw $this->createAccessDeniedException();
            }
        }
    }
}
