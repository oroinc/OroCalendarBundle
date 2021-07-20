<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurringCalendarEventExceptionConstraint;
use Oro\Bundle\CalendarBundle\Validator\RecurringCalendarEventExceptionValidator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecurringCalendarEventExceptionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var RecurringCalendarEventExceptionConstraint */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarEventManager;

    protected function setUp(): void
    {
        $this->constraint = new RecurringCalendarEventExceptionConstraint();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
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

    public function testValidateWithErrorsWorksCorrectlyIfCalendarFieldDataIsCalendarEntityObject()
    {
        $calendar = new Calendar();
        $expectedCalendarId = 42;
        $expectedCalendarAlias = 'alias';
        $this->setId($calendar, $expectedCalendarId);

        $calendarField = $this->prepareFormStub([], [], $calendar);
        $calendarAliasField = $this->prepareFormStub([], [], $expectedCalendarAlias);
        $form = $this->prepareFormStub(
            [['calendar', true], ['calendarAlias', true]],
            [['calendar', $calendarField], ['calendarAlias', $calendarAliasField]]
        );

        $this->context->expects($this->atLeastOnce())
            ->method('getRoot')
            ->willReturn($form);

        /**
         * Check Calendar entity Id passed to getCalendarUid to match method's contract
         */
        $this->calendarEventManager->expects($this->once())
            ->method('getCalendarUid')
            ->with($expectedCalendarAlias, $expectedCalendarId)
            ->willReturn('unique_calendar_uid');

        /**
         * Check validation message was added in case if Recurring event Calendar is different from
         * main event calendar
         */
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->cantChangeCalendarMessage);

        $calendarEvent = new CalendarEvent();
        $recurringEvent = new CalendarEvent();
        $recurringEvent->setRecurrence(new Recurrence());
        $this->setId($calendarEvent, 666);
        $calendarEvent->setRecurringEvent($recurringEvent);

        $this->getValidator()->validate($calendarEvent, $this->constraint);
    }

    public function testValidateWithErrorsWorksCorrectlyIfCalendarFieldDataIsInteger()
    {
        $expectedCalendarId = 42;
        $expectedCalendarAlias = 'alias';
        $calendarField = $this->prepareFormStub([], [], $expectedCalendarId);
        $calendarAliasField = $this->prepareFormStub([], [], $expectedCalendarAlias);
        $form = $this->prepareFormStub(
            [['calendar', true], ['calendarAlias', true]],
            [['calendar', $calendarField], ['calendarAlias', $calendarAliasField]]
        );

        $this->context->expects($this->atLeastOnce())
            ->method('getRoot')
            ->willReturn($form);

        $this->calendarEventManager->expects($this->once())
            ->method('getCalendarUid')
            ->with($expectedCalendarAlias, $expectedCalendarId)
            ->willReturn('unique_calendar_uid');

        /**
         * Check validation message was added in case if Recurring event Calendar is different from
         * main event calendar
         */
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->cantChangeCalendarMessage);

        $calendarEvent = new CalendarEvent();
        $recurringEvent = new CalendarEvent();
        $recurringEvent->setRecurrence(new Recurrence());
        $this->setId($calendarEvent, 666);
        $calendarEvent->setRecurringEvent($recurringEvent);

        $this->getValidator()->validate($calendarEvent, $this->constraint);
    }

    /**
     * @param array      $hasMethodMapping
     * @param array      $getMethodMapping
     * @param mixed|null $data
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareFormStub(array $hasMethodMapping, array $getMethodMapping, $data = null)
    {
        $form = $this->createMock(FormInterface::class);

        $form->expects($this->any())
            ->method('has')
            ->willReturnMap($hasMethodMapping);

        $form->expects($this->any())
            ->method('get')
            ->willReturnMap($getMethodMapping);

        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return $form;
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

    protected function setId($object, $value)
    {
        $class = new \ReflectionClass($object);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($object, $value);
    }
}
