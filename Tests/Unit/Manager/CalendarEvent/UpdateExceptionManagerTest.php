<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateExceptionManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

class UpdateExceptionManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttendeeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attendeeManager;

    /** @var DeleteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $deleteManager;

    /** @var UpdateExceptionManager */
    private $updateExceptionManager;

    protected function setUp(): void
    {
        $this->attendeeManager = $this->createMock(AttendeeManager::class);
        $this->deleteManager = $this->createMock(DeleteManager::class);

        $this->updateExceptionManager = new UpdateExceptionManager($this->attendeeManager, $this->deleteManager);
    }

    /**
     * @dataProvider recurrenceFieldsValues
     */
    public function testClearingExceptionsOnUpdate($field, $value)
    {
        $actualEvent = new CalendarEvent(1);
        $recurrence = new Recurrence();
        $actualEvent->setRecurrence($recurrence);

        $originalEvent = clone $actualEvent;

        $newRecurrence = new Recurrence();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor->setValue($newRecurrence, $field, $value);
        $actualEvent->setRecurrence($newRecurrence);

        $this->deleteManager->expects($this->once())
            ->method('deleteAndClearRecurringEventExceptions')
            ->with($actualEvent);

        $this->updateExceptionManager->onEventUpdate($actualEvent, $originalEvent);
    }

    public function recurrenceFieldsValues(): array
    {
        return [
            'Test recurrenceType changed' => ['recurrenceType', 'test_type'],
            'interval' => ['interval', 1],
            'instance' => ['instance', 2],
            'dayOfWeek' => ['dayOfWeek', ['friday']],
            'dayOfMonth' => ['dayOfMonth', 11],
            'monthOfYear' => ['monthOfYear', 11],
            'startTime' => ['startTime', new \DateTime()],
            'endTime' => ['endTime', new \DateTime()],
            'occurrences'  => ['occurrences', 11],
            'timeZone' => ['timeZone', 'Test/TimeZone'],
        ];
    }

    public function testUpdateExceptionsDataOnEventUpdate()
    {
        $originalEntity = new CalendarEvent();
        $originalEntity->setTitle('test')
            ->setDescription('Test Description')
            ->setAllDay(true);

        $exception = clone $originalEntity;
        $exception->setDescription('Changed Description');

        $entity = clone $originalEntity;
        $entity->setTitle('New Title')
            ->addRecurringEventException($exception);

        $this->updateExceptionManager->onEventUpdate($entity, $originalEntity);

        $expectedCalendarEvent = clone $originalEntity;
        $expectedCalendarEvent->setDescription('Changed Description')
            ->setTitle('New Title')
            ->setRecurringEvent($entity);
        $this->assertEquals($entity->getRecurringEventExceptions()->toArray(), [$expectedCalendarEvent]);
    }
}
