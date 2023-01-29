<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
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

class EntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $uow;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var RecurrenceModel|\PHPUnit\Framework\MockObject\MockObject */
    private $recurrenceModel;

    /** @var EntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->recurrenceModel = $this->createMock(RecurrenceModel::class);

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->listener = new EntityListener($this->tokenAccessor, $this->recurrenceModel);
    }

    /**
     * Test update of public calendar
     */
    public function testPreUpdatePublicCalendar()
    {
        $entity = new SystemCalendar();
        $entity->setOrganization(new Organization());
        $this->assertNotNull($entity->getOrganization());

        $entity->setPublic(true);

        $changeSet = [];
        $this->listener->preUpdate(new PreUpdateEventArgs($entity, $this->em, $changeSet));
        $this->assertNull($entity->getOrganization());
    }

    /**
     * Test update of system calendar
     */
    public function testPreUpdateSystemCalendar()
    {
        $organization = new Organization();

        $entity = new SystemCalendar();
        $this->assertNull($entity->getOrganization());

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $changeSet = [];
        $this->listener->preUpdate(new PreUpdateEventArgs($entity, $this->em, $changeSet));
        $this->assertSame($organization, $entity->getOrganization());
    }

    /**
     * Test new user creation
     */
    public function testOnFlushCreateUser()
    {
        $user = new User();
        $org1 = new Organization();
        ReflectionUtil::setId($org1, 1);
        $org2 = new Organization();
        ReflectionUtil::setId($org2, 2);
        $user->setOrganization($org1);
        $user->addOrganization($org1);
        $user->addOrganization($org2);

        $newCalendar1 = new Calendar();
        $newCalendar1->setOwner($user)->setOrganization($org1);
        $newCalendar2 = new Calendar();
        $newCalendar2->setOwner($user)->setOrganization($org2);

        $calendarMetadata = new ClassMetadata(get_class($newCalendar1));

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$user]);
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Calendar::class)
            ->willReturn($calendarMetadata);
        $this->em->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$this->equalTo($newCalendar1)],
                [$this->equalTo($newCalendar2)]
            );
        $this->uow->expects($this->exactly(2))
            ->method('computeChangeSet')
            ->withConsecutive(
                [$this->identicalTo($calendarMetadata), $this->equalTo($newCalendar1)],
                [$this->identicalTo($calendarMetadata), $this->equalTo($newCalendar2)]
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
        $org = new Organization();
        ReflectionUtil::setId($org, 1);

        $coll = $this->getPersistentCollection($user, ['fieldName' => 'organizations'], [$org]);

        $newCalendar = new Calendar();
        $newCalendar->setOwner($user);
        $newCalendar->setOrganization($org);

        $calendarMetadata = new ClassMetadata(get_class($newCalendar));

        $calendarRepo = $this->createMock(CalendarRepository::class);
        $calendarRepo->expects($this->any())
            ->method('findDefaultCalendar')
            ->willReturn(false);

        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects($this->once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$coll]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->willReturn($calendarRepo);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($newCalendar));
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Calendar::class)
            ->willReturn($calendarMetadata);

        $this->uow->expects($this->once())
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
    }

    public function testPrePersistShouldCalculateEndTimeForRecurrenceEntity()
    {
        $recurrence = new Recurrence();
        $calculatedEndTime = new \DateTime();

        // guard
        $this->assertNotEquals($calculatedEndTime, $recurrence->getCalculatedEndTime());

        $this->recurrenceModel->expects($this->once())
            ->method('getCalculatedEndTime')
            ->with($recurrence)
            ->willReturn($calculatedEndTime);

        $this->listener->prePersist(new LifecycleEventArgs($recurrence, $this->em));
        $this->assertEquals($calculatedEndTime, $recurrence->getCalculatedEndTime());
    }

    public function testPrePersistShouldNotCalculateEndTimeForOtherThanRecurrenceEntity()
    {
        $this->recurrenceModel->expects($this->never())
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
        $this->assertNotNull($entity->getOrganization());

        $entity->setPublic(true);

        $this->listener->prePersist(new LifecycleEventArgs($entity, $this->em));
        $this->assertNull($entity->getOrganization());
    }

    public function testPrePersistSystemCalendar()
    {
        $organization = new Organization();

        $entity = new SystemCalendar();
        $this->assertNull($entity->getOrganization());

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->listener->prePersist(new LifecycleEventArgs($entity, $this->em));
        $this->assertSame($organization, $entity->getOrganization());
    }
}
