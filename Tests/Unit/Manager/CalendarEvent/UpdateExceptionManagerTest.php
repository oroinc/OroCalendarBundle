<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\UpdateExceptionManager;
use Oro\Component\PropertyAccess\PropertyAccessor;

class UpdateExceptionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UpdateExceptionManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->manager = new UpdateExceptionManager();
    }

    /**
     * @dataProvider recurrenceFieldsValues
     */
    public function testClearingExceptionsOnUpdate($field, $value)
    {
        $originalEntity = new CalendarEvent();
        $originalRecurrence = new Recurrence();
        $originalEntity->setRecurrence($originalRecurrence);

        $entity = new CalendarEvent();

        $newRecurrence = new Recurrence();
        $propertyAccessor = new PropertyAccessor();
        $propertyAccessor->setValue($newRecurrence, $field, $value);
        $entity->setRecurrence($newRecurrence);

        $entity->addRecurringEventException(new CalendarEvent());

        $this->manager->onEventUpdate($entity, $originalEntity);

        $this->assertCount(0, $entity->getRecurringEventExceptions());
    }

    /**
     * @return array
     */
    public function recurrenceFieldsValues()
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

        $this->manager->onEventUpdate($entity, $originalEntity);

        $expectedCalendarEvent = clone $originalEntity;
        $expectedCalendarEvent->setDescription('Changed Description')
            ->setTitle('New Title')
            ->setRecurringEvent($entity);
        $this->assertEquals($entity->getRecurringEventExceptions()->toArray(), [$expectedCalendarEvent]);
    }
}
