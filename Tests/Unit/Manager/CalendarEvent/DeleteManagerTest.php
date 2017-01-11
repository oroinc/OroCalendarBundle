<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;

class DeleteManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var DeleteManager
     */
    protected $deleteManager;

    protected function setUp()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->objectManager);

        $this->deleteManager = new DeleteManager($doctrine);
    }

    public function testDeleteAndClearRecurringEventExceptions()
    {
        $event = new CalendarEvent(1);
        $recurrence = new Recurrence();
        $event->setRecurrence($recurrence);
        $childEvent = new CalendarEvent(3);
        $event->addChildEvent($childEvent);

        $exceptionEvent = new CalendarEvent(2);
        $event->addRecurringEventException($exceptionEvent);

        $childExceptionEvent = new CalendarEvent(4);
        $childEvent->addRecurringEventException($childExceptionEvent);

        $this->objectManager->expects($this->exactly(2))
            ->method('remove');

        $this->objectManager->expects($this->at(0))
            ->method('remove')
            ->with($exceptionEvent);

        $this->objectManager->expects($this->at(1))
            ->method('remove')
            ->with($childExceptionEvent);

        $this->deleteManager->deleteAndClearRecurringEventExceptions($event);

        $this->assertCount(0, $event->getRecurringEventExceptions());
        $this->assertCount(0, $childEvent->getRecurringEventExceptions());
    }
}
