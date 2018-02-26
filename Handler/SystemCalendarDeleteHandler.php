<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

/**
 * Delete handler for SystemCalendar entities.
 */
class SystemCalendarDeleteHandler extends DeleteHandler
{
    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param SystemCalendarConfig $calendarConfig
     */
    public function setCalendarConfig(SystemCalendarConfig $calendarConfig)
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
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        if ($entity->isPublic()) {
            if (!$this->calendarConfig->isPublicCalendarEnabled()) {
                throw new ForbiddenException('Public calendars are disabled.');
            } elseif (!$this->authorizationChecker->isGranted('oro_public_calendar_management')) {
                throw new ForbiddenException('Access denied.');
            }
        } else {
            if (!$this->calendarConfig->isSystemCalendarEnabled()) {
                throw new ForbiddenException('System calendars are disabled.');
            } elseif (!$this->authorizationChecker->isGranted('oro_system_calendar_management')
                || $entity->getOrganization()->getId() !== $this->tokenAccessor->getOrganizationId()
            ) {
                throw new ForbiddenException('Access denied.');
            }
        }
    }
}
