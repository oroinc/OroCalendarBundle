<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The delete handler extension for CalendarEvent entity.
 */
class CalendarEventDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /** @var SystemCalendarConfig */
    private $calendarConfig;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var NotificationManager */
    private $notificationManager;

    public function __construct(
        SystemCalendarConfig $calendarConfig,
        AuthorizationCheckerInterface $authorizationChecker,
        NotificationManager $notificationManager
    ) {
        $this->calendarConfig = $calendarConfig;
        $this->authorizationChecker = $authorizationChecker;
        $this->notificationManager = $notificationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var CalendarEvent $entity */

        $calendar = $entity->getSystemCalendar();
        if ($calendar) {
            if ($calendar->isPublic()) {
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
            }
        } elseif (!$this->authorizationChecker->isGranted('VIEW', $entity->getCalendar())) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postFlush($entity, array $options): void
    {
        /** @var CalendarEvent $entity */

        $notifyStrategy = $options['notifyAttendees'] ?? NotificationManager::ALL_NOTIFICATIONS_STRATEGY;
        $em = $this->getEntityManager($entity);
        if ($em->contains($entity) && $entity->isCancelled()) {
            $this->notificationManager->onUpdate($entity, $options['originalEntity'], $notifyStrategy);
        } else {
            $this->notificationManager->onDelete($entity, $notifyStrategy);
        }
    }
}
