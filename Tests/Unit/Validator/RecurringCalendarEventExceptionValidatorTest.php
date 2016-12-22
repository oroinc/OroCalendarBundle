<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurringCalendarEventExceptionConstraint;
use Oro\Bundle\CalendarBundle\Validator\RecurringCalendarEventExceptionValidator;

class RecurringCalendarEventExceptionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarEvent */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventManager;

    protected function setUp()
    {
        $this->constraint = new RecurringCalendarEventExceptionConstraint();
        $this->context = $this->createMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->calendarEventManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidateNoErrors()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $calendarEvent = new CalendarEvent();

        $this->getValidator()->validate($calendarEvent, $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $this->context->expects($this->at(0))
            ->method('addViolation')
            ->with($this->equalTo("Parameter 'recurringEventId' can't have the same value as calendar event ID."));
        $this->context->expects($this->at(1))
            ->method('addViolation')
            ->with($this->equalTo("Parameter 'recurringEventId' can be set only for recurring calendar events."));
        $this->context->expects($this->at(2))
            ->method('getRoot');

        $calendarEvent = new CalendarEvent();
        $recurringEvent = new CalendarEvent();
        $this->setId($recurringEvent, 666);
        $this->setId($calendarEvent, 666);
        $calendarEvent->setRecurringEvent($recurringEvent);

        $this->getValidator()->validate($calendarEvent, $this->constraint);
    }

    /**
     * @return RecurringCalendarEventExceptionValidator
     */
    protected function getValidator()
    {
        $validator = new RecurringCalendarEventExceptionValidator($this->calendarEventManager);
        $validator->initialize($this->context);

        return $validator;
    }

    /**
     * @param $object
     * @param $value
     */
    protected function setId($object, $value)
    {
        $class = new \ReflectionClass($object);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($object, $value);
    }
}
