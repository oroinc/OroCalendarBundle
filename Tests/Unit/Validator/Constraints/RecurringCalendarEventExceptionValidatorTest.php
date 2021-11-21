<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurringCalendarEventException;
use Oro\Bundle\CalendarBundle\Validator\Constraints\RecurringCalendarEventExceptionValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RecurringCalendarEventExceptionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventManager;

    protected function setUp(): void
    {
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new RecurringCalendarEventExceptionValidator($this->calendarEventManager);
    }

    private function prepareFormStub(array $hasMethodMapping, array $getMethodMapping, $data = null): FormInterface
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

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(CalendarEvent::class), $this->createMock(Constraint::class));
    }

    public function testValueIsNotCalendarEvent()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('test', new RecurringCalendarEventException());
    }

    public function testValidateNoErrors()
    {
        $this->validator->validate(new CalendarEvent(), new RecurringCalendarEventException());

        $this->assertNoViolation();
    }

    public function testValidateWithErrors()
    {
        $calendarEvent = new CalendarEvent();
        $recurringEvent = new CalendarEvent();
        ReflectionUtil::setId($recurringEvent, 123);
        ReflectionUtil::setId($calendarEvent, 123);
        $calendarEvent->setRecurringEvent($recurringEvent);

        $constraint = new RecurringCalendarEventException();
        $this->validator->validate($calendarEvent, $constraint);

        $this
            ->buildViolation($constraint->selfRelationMessage)
            ->buildNextViolation($constraint->wrongRecurrenceMessage)
            ->assertRaised();
    }

    public function testValidateWithErrorsWorksCorrectlyIfCalendarFieldDataIsCalendarEntityObject()
    {
        $calendar = new Calendar();
        $expectedCalendarId = 42;
        $expectedCalendarAlias = 'alias';
        ReflectionUtil::setId($calendar, $expectedCalendarId);

        $calendarField = $this->prepareFormStub([], [], $calendar);
        $calendarAliasField = $this->prepareFormStub([], [], $expectedCalendarAlias);
        $form = $this->prepareFormStub(
            [['calendar', true], ['calendarAlias', true]],
            [['calendar', $calendarField], ['calendarAlias', $calendarAliasField]]
        );

        /**
         * Check Calendar entity Id passed to getCalendarUid to match method's contract
         */
        $this->calendarEventManager->expects($this->once())
            ->method('getCalendarUid')
            ->with($expectedCalendarAlias, $expectedCalendarId)
            ->willReturn('unique_calendar_uid');

        $calendarEvent = new CalendarEvent();
        $recurringEvent = new CalendarEvent();
        $recurringEvent->setRecurrence(new Recurrence());
        ReflectionUtil::setId($calendarEvent, 123);
        $calendarEvent->setRecurringEvent($recurringEvent);

        $constraint = new RecurringCalendarEventException();
        $this->setRoot($form);
        $this->validator->validate($calendarEvent, $constraint);

        /**
         * Check validation message was added in case if Recurring event Calendar is different from
         * main event calendar
         */
        $this->buildViolation($constraint->cantChangeCalendarMessage)
            ->assertRaised();
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

        $this->calendarEventManager->expects($this->once())
            ->method('getCalendarUid')
            ->with($expectedCalendarAlias, $expectedCalendarId)
            ->willReturn('unique_calendar_uid');

        $calendarEvent = new CalendarEvent();
        $recurringEvent = new CalendarEvent();
        $recurringEvent->setRecurrence(new Recurrence());
        ReflectionUtil::setId($calendarEvent, 123);
        $calendarEvent->setRecurringEvent($recurringEvent);

        $constraint = new RecurringCalendarEventException();
        $this->setRoot($form);
        $this->validator->validate($calendarEvent, $constraint);

        /**
         * Check validation message was added in case if Recurring event Calendar is different from
         * main event calendar
         */
        $this->buildViolation($constraint->cantChangeCalendarMessage)
            ->assertRaised();
    }
}
