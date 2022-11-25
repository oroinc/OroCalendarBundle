<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Validator\Constraints\ReminderStartDate;
use Oro\Bundle\CalendarBundle\Validator\Constraints\ReminderStartDateConstraintValidator;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ReminderStartDateConstraintValidatorTest extends ConstraintValidatorTestCase
{
    /** @var CalendarEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEvent;

    /** @var Reminder|\PHPUnit\Framework\MockObject\MockObject */
    private $reminder;

    protected function setUp(): void
    {
        $this->calendarEvent = $this->createMock(CalendarEvent::class);
        $this->reminder = $this->createMock(Reminder::class);

        $form = $this->createMock(Form::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($this->calendarEvent);

        parent::setUp();

        $this->setRoot($form);
    }

    protected function createValidator(): ReminderStartDateConstraintValidator
    {
        return new ReminderStartDateConstraintValidator();
    }

    public function testValidReminderInterval(): void
    {
        $this->reminder->expects(self::once())
            ->method('getInterval')
            ->willReturn(new ReminderInterval('1', 'D'));

        $this->calendarEvent->expects(self::once())
            ->method('getStart')
            ->willReturn(new \DateTime('+2day', new \DateTimeZone('UTC')));

        $this->validator->validate(
            new ArrayCollection([$this->reminder]),
            new ReminderStartDate()
        );

        $this->assertNoViolation();
    }

    public function testInvalidReminderInterval(): void
    {
        $this->reminder->expects(self::once())
            ->method('getInterval')
            ->willReturn(new ReminderInterval('1', 'D'));

        $reminderBar = $this->createMock(Reminder::class);
        $reminderBar->expects(self::once())
            ->method('getInterval')
            ->willReturn(new ReminderInterval('5', 'D'));

        $this->calendarEvent->expects(self::any())
            ->method('getStart')
            ->willReturn(new \DateTime('+2 days', new \DateTimeZone('UTC')));

        $this->validator->validate(
            new ArrayCollection([$this->reminder, $reminderBar]),
            new ReminderStartDate()
        );

        $this->buildViolation('oro.calendar.calendar_event.reminder.date_start_less_than_now.message')
            ->atPath('property.path[1].interval.number')
            ->assertRaised();
    }

    public function testInvalidParameterException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ReminderBundle\Entity\Reminder", "int" given'
        );
        $this->validator->validate(new ArrayCollection([1]), new ReminderStartDate());
    }

    public function testInvalidConstraintException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                ReminderStartDate::class,
                NotNull::class
            )
        );
        $this->validator->validate($this->calendarEvent, new NotNull());
    }
}
