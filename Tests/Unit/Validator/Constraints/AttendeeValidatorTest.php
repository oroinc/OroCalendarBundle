<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Validator\Constraints\Attendee;
use Oro\Bundle\CalendarBundle\Validator\Constraints\AttendeeValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttendeeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new AttendeeValidator();
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testValidValues(AttendeeEntity $value)
    {
        $this->validator->validate($value, new Attendee());

        $this->assertNoViolation();
    }

    public function validValuesProvider(): array
    {
        return [
            [
                (new AttendeeEntity())
                    ->setEmail('email@example.com')
                    ->setCalendarEvent(new CalendarEvent(1))
            ],
            [
                (new AttendeeEntity())
                    ->setDisplayName('name')
                    ->setCalendarEvent(new CalendarEvent(2))
            ],
            [
                (new AttendeeEntity())
                    ->setDisplayName('name')
                    ->setEmail('email@example.com')
                    ->setCalendarEvent(new CalendarEvent(3))
            ],
            [
                (new AttendeeEntity())
                    ->setCalendarEvent((new CalendarEvent(4))->setSystemCalendar(new SystemCalendar()))
            ],
        ];
    }

    public function testInvalidValue()
    {
        $value = (new AttendeeEntity())->setCalendarEvent(new CalendarEvent(1));

        $constraint = new Attendee();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
