<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Recurrence as RecurrenceEntity;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Calendar entities listener. Checks organization column for SystemCalendar entities, creates new calendar
 * on user creation, updates 'calculatedEndTime' field value for Recurrence calendar events.
 */
class EntityListener
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ClassMetadata[] */
    protected $metadataLocalCache = [];

    /** @var Calendar[] */
    protected $insertedCalendars = [];

    /** @var Recurrence  */
    protected $recurrenceModel;

    public function __construct(TokenAccessorInterface $tokenAccessor, Recurrence $recurrenceModel)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->recurrenceModel = $recurrenceModel;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->processEntity($args->getEntity());
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->processEntity($args->getEntity());
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $em  = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                $this->createCalendarsForNewUser($em, $uow, $entity);
            }
        }
        /** @var PersistentCollection $coll */
        foreach ($uow->getScheduledCollectionUpdates() as $coll) {
            $collOwner = $coll->getOwner();
            if ($collOwner instanceof User
                && $collOwner->getId()
                && $coll->getMapping()['fieldName'] === 'organizations'
            ) {
                foreach ($coll->getInsertDiff() as $entity) {
                    if (!$this->isCalendarExists($em, $collOwner, $entity)) {
                        $this->createCalendar($em, $uow, $collOwner, $entity);
                    }
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event)
    {
        if (!empty($this->insertedCalendars)) {
            $em = $event->getEntityManager();
            foreach ($this->insertedCalendars as $calendar) {
                // connect the calendar to itself
                $calendarProperty = new CalendarProperty();
                $calendarProperty
                    ->setTargetCalendar($calendar)
                    ->setCalendarAlias(Calendar::CALENDAR_ALIAS)
                    ->setCalendar($calendar->getId());
                $em->persist($calendarProperty);
            }
            $this->insertedCalendars = [];
            $em->flush();
        }
    }

    /**
     * @param EntityManager $em
     * @param UnitOfWork    $uow
     * @param User          $user
     */
    protected function createCalendarsForNewUser($em, $uow, $user)
    {
        $owningOrganization = $user->getOrganization();
        $this->createCalendar($em, $uow, $user, $owningOrganization);
        foreach ($user->getOrganizations() as $organization) {
            if ($organization->getId() !== $owningOrganization->getId()) {
                $this->createCalendar($em, $uow, $user, $organization);
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param UnitOfWork    $uow
     * @param User          $user
     * @param Organization  $organization
     */
    protected function createCalendar($em, $uow, $user, $organization)
    {
        // create default user's calendar
        $calendar = new Calendar();
        $calendar
            ->setOwner($user)
            ->setOrganization($organization);
        $em->persist($calendar);
        $uow->computeChangeSet($this->getClassMetadata($calendar, $em), $calendar);

        $this->insertedCalendars[] = $calendar;
    }

    /**
     * @param EntityManager $em
     * @param User          $user
     * @param Organization  $organization
     *
     * @return bool
     */
    protected function isCalendarExists(EntityManager $em, User $user, Organization $organization)
    {
        $calendarRepository = $em->getRepository('OroCalendarBundle:Calendar');

        return (bool)$calendarRepository->findDefaultCalendar($user->getId(), $organization->getId());
    }

    /**
     * @param object        $entity
     * @param EntityManager $em
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata($entity, EntityManager $em)
    {
        $className = ClassUtils::getClass($entity);
        if (!isset($this->metadataLocalCache[$className])) {
            $this->metadataLocalCache[$className] = $em->getClassMetadata($className);
        }

        return $this->metadataLocalCache[$className];
    }

    /**
     * @param object $entity
     */
    protected function processEntity($entity)
    {
        if ($entity instanceof SystemCalendar) {
            if ($entity->isPublic()) {
                // make sure that public calendar doesn't belong to any organization
                $entity->setOrganization(null);
            } elseif (!$entity->getOrganization()) {
                // make sure an organization is set for system calendar
                $organization = $this->tokenAccessor->getOrganization();
                if ($organization) {
                    $entity->setOrganization($organization);
                }
            }
        }

        if ($entity instanceof RecurrenceEntity) {
            $entity->setCalculatedEndTime($this->recurrenceModel->getCalculatedEndTime($entity));
        }
    }
}
