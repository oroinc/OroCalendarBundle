<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\EventListener\EntityListener;
use Oro\Bundle\CalendarBundle\Model\Recurrence as RecurrenceModel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityListenerTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $em;

    /** @var UnitOfWork|MockObject */
    private $uow;

    /** @var TokenAccessorInterface|MockObject */
    private $tokenAccessor;

    /** @var RecurrenceModel|MockObject */
    private $recurrenceModel;

    /** @var EntityListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->recurrenceModel = $this->createMock(RecurrenceModel::class);

        $this->em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $container = TestContainerBuilder::create()
            ->add(RecurrenceModel::class, $this->recurrenceModel)
            ->getContainer($this);

        $this->listener = new EntityListener($this->tokenAccessor, $container);
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, $id);

        return $organization;
    }

    /**
     * Test update of public calendar
     */
    public function testPreUpdatePublicCalendar()
    {
        $entity = new SystemCalendar();
        $entity->setOrganization(new Organization());
        self::assertNotNull($entity->getOrganization());

        $entity->setPublic(true);

        $changeSet = [];
        $this->listener->preUpdate(new PreUpdateEventArgs($entity, $this->em, $changeSet));
        self::assertNull($entity->getOrganization());
    }

    /**
     * Test update of system calendar
     */
    public function testPreUpdateSystemCalendar()
    {
        $organization = new Organization();

        $entity = new SystemCalendar();
        self::assertNull($entity->getOrganization());

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $changeSet = [];
        $this->listener->preUpdate(new PreUpdateEventArgs($entity, $this->em, $changeSet));
        self::assertSame($organization, $entity->getOrganization());
    }

    /**
     * Test new user creation
     */
    public function testOnFlushCreateUser()
    {
        $user = new User();
        $org1 = $this->getOrganization(1);
        $org2 = $this->getOrganization(2);
        $user->setOrganization($org1);
        $user->addOrganization($org1);
        $user->addOrganization($org2);

        $newCalendar1 = new Calendar();
        $newCalendar1->setOwner($user)->setOrganization($org1);
        $newCalendar2 = new Calendar();
        $newCalendar2->setOwner($user)->setOrganization($org2);

        $calendarMetadata = new ClassMetadata(get_class($newCalendar1));

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$user]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(Calendar::class)
            ->willReturn($calendarMetadata);
        $this->em->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive([$newCalendar1], [$newCalendar2]);
        $this->uow->expects(self::exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [self::identicalTo($calendarMetadata), $newCalendar1],
                [self::identicalTo($calendarMetadata), $newCalendar2]
            );

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    /**
     * Test existing user modification
     */
    public function testOnFlushUpdateUser()
    {
        $user = new User();
        ReflectionUtil::setId($user, 123);
        $org = $this->getOrganization(1);

        $coll = $this->getPersistentCollection($user, ['fieldName' => 'organizations'], [$org]);

        $newCalendar = new Calendar();
        $newCalendar->setOwner($user);
        $newCalendar->setOrganization($org);

        $calendarMetadata = new ClassMetadata(get_class($newCalendar));

        $calendarRepo = $this->createMock(CalendarRepository::class);
        $calendarRepo->expects(self::any())
            ->method('findDefaultCalendar')
            ->willReturn(false);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$coll]);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(Calendar::class)
            ->willReturn($calendarRepo);
        $this->em->expects(self::once())
            ->method('persist')
            ->with($newCalendar);
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(Calendar::class)
            ->willReturn($calendarMetadata);

        $this->uow->expects(self::once())
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    public function testPrePersistShouldCalculateEndTimeForRecurrenceEntity()
    {
        $recurrence = new Recurrence();
        $calculatedEndTime = new \DateTime();

        // guard
        self::assertNotSame($calculatedEndTime, $recurrence->getCalculatedEndTime());

        $this->recurrenceModel->expects(self::once())
            ->method('getCalculatedEndTime')
            ->with($recurrence)
            ->willReturn($calculatedEndTime);

        $this->listener->prePersist(new LifecycleEventArgs($recurrence, $this->em));
        self::assertSame($calculatedEndTime, $recurrence->getCalculatedEndTime());
    }

    public function testPrePersistShouldNotCalculateEndTimeForOtherThanRecurrenceEntity()
    {
        $this->recurrenceModel->expects(self::never())
            ->method('getCalculatedEndTime');

        $this->listener->prePersist(new LifecycleEventArgs(new Organization(), $this->em));
    }

    private function getPersistentCollection(object $owner, array $mapping, array $items = []): PersistentCollection
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $coll = new PersistentCollection(
            $this->em,
            $metadata,
            new ArrayCollection($items)
        );

        $mapping['inversedBy'] = 'test';
        $coll->setOwner($owner, $mapping);

        return $coll;
    }

    public function testPrePersistPublicCalendar()
    {
        $entity = new SystemCalendar();
        $entity->setOrganization(new Organization());
        self::assertNotNull($entity->getOrganization());

        $entity->setPublic(true);

        $this->listener->prePersist(new LifecycleEventArgs($entity, $this->em));
        self::assertNull($entity->getOrganization());
    }

    public function testPrePersistSystemCalendar()
    {
        $organization = new Organization();

        $entity = new SystemCalendar();
        self::assertNull($entity->getOrganization());

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->listener->prePersist(new LifecycleEventArgs($entity, $this->em));
        self::assertSame($organization, $entity->getOrganization());
    }
}
