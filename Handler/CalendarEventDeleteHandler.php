<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class CalendarEventDeleteHandler extends DeleteHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var NotificationManager */
    protected $notificationManager;

    /** @var DeleteManager */
    protected $deleteManager;

    /**
     * @param RequestStack $requestStack
     *
     * @return CalendarEventDeleteHandler
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    /**
     * @param NotificationManager $notificationManager
     *
     * @return CalendarEventDeleteHandler
     */
    public function setNotificationManager(NotificationManager $notificationManager)
    {
        $this->notificationManager = $notificationManager;

        return $this;
    }

    /**
     * @param SystemCalendarConfig $calendarConfig
     *
     * @return CalendarEventDeleteHandler
     */
    public function setCalendarConfig(SystemCalendarConfig $calendarConfig)
    {
        $this->calendarConfig = $calendarConfig;

        return $this;
    }

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     *
     * @return CalendarEventDeleteHandler
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;

        return $this;
    }

    /**
     * @param DeleteManager $deleteManager
     */
    public function setDeleteManager($deleteManager)
    {
        $this->deleteManager = $deleteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        /** @var CalendarEvent $entity */
        /** @var SystemCalendar|null $calendar */
        $calendar = $entity->getSystemCalendar();
        if ($calendar) {
            if ($calendar->isPublic()) {
                if (!$this->calendarConfig->isPublicCalendarEnabled()) {
                    throw new ForbiddenException('Public calendars are disabled.');
                }

                if (!$this->authorizationChecker->isGranted('oro_public_calendar_management')) {
                    throw new ForbiddenException('Access denied.');
                }
            } else {
                if (!$this->calendarConfig->isSystemCalendarEnabled()) {
                    throw new ForbiddenException('System calendars are disabled.');
                }

                if (!$this->authorizationChecker->isGranted('oro_system_calendar_management')) {
                    throw new ForbiddenException('Access denied.');
                }
            }
        } else {
            // for regular calendar event, check access by it's calendar
            // todo: Temporary solution. Should be deleted in scope of BAP-13256
            if (!$this->authorizationChecker->isGranted('VIEW', $entity->getCalendar())) {
                throw new ForbiddenException('Access denied.');
            }
            parent::checkPermissions($entity, $em);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        /** @var CalendarEvent $entity */
        $entity = $manager->find($id);
        if (!$entity) {
            throw new EntityNotFoundException();
        }

        $em = $manager->getObjectManager();
        $this->processDelete($entity, $em);
    }

    /**
     * @param CalendarEvent $entity
     *
     * {@inheritdoc}
     */
    public function processDelete($entity, ObjectManager $em)
    {
        $this->checkPermissions($entity, $em);
        // entity is cloned to have all attributes in delete notification email
        $clonedEntity = clone $entity;

        $this->deleteManager->deleteOrCancel($entity, $this->shouldCancelInsteadDelete());

        $em->flush();

        if ($em->contains($entity) && $entity->isCancelled()) {
            $this->notificationManager->onUpdate($entity, $clonedEntity, $this->getNotificationStrategy());
        } else {
            $this->notificationManager->onDelete($entity, $this->getNotificationStrategy());
        }
    }
    
    /**
     * @return string
     */
    protected function getNotificationStrategy()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return NotificationManager::ALL_NOTIFICATIONS_STRATEGY;
        }

        return $request->query->get('notifyAttendees', NotificationManager::NONE_NOTIFICATIONS_STRATEGY);
    }

    /**
     * @return bool
     */
    protected function shouldCancelInsteadDelete()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request && (bool) $request->query->get('isCancelInsteadDelete', false);
    }
}
