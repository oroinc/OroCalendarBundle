<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\EventListener\CalendarEventEntityListener;
use Oro\Bundle\CalendarBundle\Exception\UidAlreadySetException;

class CalendarEventEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    private const UID = 'MOCK-UUID-123456';

    /** @var CalendarEventEntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new CalendarEventEntityListener();
    }

    public function testPrePersistSetParentUidToChildrenIfSet()
    {
        $child1 = new CalendarEvent();
        $child2 = new CalendarEvent();

        $parent = new CalendarEvent();
        $parent->addChildEvent($child1)
            ->addChildEvent($child2)
            ->setUid(self::UID);

        $this->listener->prePersist($parent, $this->getLifecycleEvent());

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child1->getUid());
        $this->assertSame(self::UID, $child2->getUid());
    }

    public function testPrePersistSetChildUidToParent()
    {
        $child = new CalendarEvent();
        $child->setUid(self::UID);

        $parent = new CalendarEvent();
        $parent->addChildEvent($child);

        $this->listener->prePersist($child, $this->getLifecycleEvent());

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child->getUid());
    }

    public function testPrePersistSetChildUidToParentAndAllItsChildren()
    {
        $child1 = new CalendarEvent();
        $child1->setUid(self::UID);
        $child2 = new CalendarEvent();
        $child3 = new CalendarEvent();

        $parent = new CalendarEvent();
        $parent->addChildEvent($child1)
            ->addChildEvent($child2)
            ->addChildEvent($child3);

        $this->listener->prePersist($child1, $this->getLifecycleEvent());

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child1->getUid());
        $this->assertSame(self::UID, $child2->getUid());
        $this->assertSame(self::UID, $child3->getUid());
    }

    public function testPreUpdateSetChildUidToParent()
    {
        $eventMock = $this->getPreUpdateEvent(true, null, '123');
        /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $unitOfWork */
        $unitOfWork = $eventMock->getEntityManager()->getUnitOfWork();
        $unitOfWork->expects($this->atLeastOnce())
            ->method('scheduleExtraUpdate');

        $child = new CalendarEvent();
        $child->setUid(self::UID);

        $parent = new CalendarEvent();
        $parent->addChildEvent($child);

        $this->listener->preUpdate($child, $eventMock);

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child->getUid());
    }

    public function testPreUpdateSetChildUidToParentAndAllItsChildren()
    {
        $eventMock = $this->getPreUpdateEvent(true, null, '123');
        /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $unitOfWork */
        $unitOfWork = $eventMock->getEntityManager()->getUnitOfWork();
        $unitOfWork->expects($this->atLeastOnce())
            ->method('scheduleExtraUpdate');

        $child1 = new CalendarEvent();
        $child1->setUid(self::UID);
        $child2 = new CalendarEvent();
        $child3 = new CalendarEvent();

        $parent = new CalendarEvent();
        $parent->addChildEvent($child1)
            ->addChildEvent($child2)
            ->addChildEvent($child3);

        $this->listener->preUpdate($child1, $eventMock);

        $this->assertSame(self::UID, $parent->getUid());
        $this->assertSame(self::UID, $child1->getUid());
        $this->assertSame(self::UID, $child2->getUid());
        $this->assertSame(self::UID, $child3->getUid());
    }

    public function testPreUpdateAllowsUpdateWhenUidIsNotChangedOrNullOrTheSame()
    {
        $this->listener->preUpdate(new CalendarEvent(), $this->getPreUpdateEvent(false));
        $this->listener->preUpdate(new CalendarEvent(), $this->getPreUpdateEvent(true, '123', '123'));
        $this->listener->preUpdate(new CalendarEvent(), $this->getPreUpdateEvent(true, null, '123'));
    }

    public function testPreUpdateThrowExceptionIfUidAlreadySet()
    {
        $this->expectException(UidAlreadySetException::class);

        $this->listener->preUpdate(new CalendarEvent(), $this->getPreUpdateEvent(true, '123', '1234'));
    }

    private function getLifecycleEvent(): LifecycleEventArgs
    {
        return new LifecycleEventArgs(new \stdClass(), $this->createMock(ObjectManager::class));
    }

    private function getPreUpdateEvent(
        bool $hasUidFieldChanged,
        string $oldValue = null,
        string $newValue = null
    ): PreUpdateEventArgs {
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));

        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
        $event->expects($this->any())
            ->method('hasChangedField')
            ->willReturn($hasUidFieldChanged);
        $event->expects($this->any())
            ->method('getOldValue')
            ->willReturn($oldValue);
        $event->expects($this->any())
            ->method('getNewValue')
            ->willReturn($newValue);

        return $event;
    }
}
