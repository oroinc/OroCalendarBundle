<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Recurrence as RecurrenceEntity;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Calendar entities listener. Checks organization column for SystemCalendar entities, creates new calendar
 * on user creation, updates 'calculatedEndTime' field value for Recurrence calendar events.
 */
class EntityListener implements ServiceSubscriberInterface
{
    private TokenAccessorInterface $tokenAccessor;
    private ContainerInterface $container;
    /** @var ClassMetadata[] */
    private array $metadataLocalCache = [];
    /** @var Calendar[] */
    private array $insertedCalendars = [];

    public function __construct(TokenAccessorInterface $tokenAccessor, ContainerInterface $container)
    {
        $this->tokenAccessor = $tokenAccessor;
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [Recurrence::class];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->processEntity($args->getObject());
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->processEntity($args->getObject());
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em  = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                $this->createCalendarsForNewUser($em, $uow, $entity);
            }
        }
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

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!empty($this->insertedCalendars)) {
            $em = $event->getObjectManager();
            foreach ($this->insertedCalendars as $calendar) {
                // connect the calendar to itself
                $calendarProperty = new CalendarProperty();
                $calendarProperty->setTargetCalendar($calendar);
                $calendarProperty->setCalendarAlias(Calendar::CALENDAR_ALIAS);
                $calendarProperty->setCalendar($calendar->getId());
                $em->persist($calendarProperty);
            }
            $this->insertedCalendars = [];
            $em->flush();
        }
    }

    private function createCalendarsForNewUser(EntityManagerInterface $em, UnitOfWork $uow, User $user): void
    {
        $owningOrganization = $user->getOrganization();
        $this->createCalendar($em, $uow, $user, $owningOrganization);
        foreach ($user->getOrganizations() as $organization) {
            if ($organization->getId() !== $owningOrganization->getId()) {
                $this->createCalendar($em, $uow, $user, $organization);
            }
        }
    }

    private function createCalendar(
        EntityManagerInterface $em,
        UnitOfWork $uow,
        User $user,
        ?Organization $organization
    ): void {
        // create default user's calendar
        $calendar = new Calendar();
        $calendar->setOwner($user);
        $calendar->setOrganization($organization);
        $em->persist($calendar);
        $uow->computeChangeSet($this->getClassMetadata($calendar, $em), $calendar);

        $this->insertedCalendars[] = $calendar;
    }

    private function isCalendarExists(EntityManager $em, User $user, Organization $organization): bool
    {
        $calendarRepository = $em->getRepository(Calendar::class);

        return (bool)$calendarRepository->findDefaultCalendar($user->getId(), $organization->getId());
    }

    private function getClassMetadata(object $entity, EntityManager $em): ClassMetadata
    {
        $className = ClassUtils::getClass($entity);
        if (!isset($this->metadataLocalCache[$className])) {
            $this->metadataLocalCache[$className] = $em->getClassMetadata($className);
        }

        return $this->metadataLocalCache[$className];
    }

    private function processEntity(object $entity): void
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
            $entity->setCalculatedEndTime($this->getRecurrenceModel()->getCalculatedEndTime($entity));
        }
    }

    private function getRecurrenceModel(): Recurrence
    {
        return $this->container->get(Recurrence::class);
    }
}
