<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Validator\Constraints\Attendee;
use Oro\Bundle\CalendarBundle\Validator\Constraints\AttendeeValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AttendeeValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExecutionContextInterface */
    protected $context;

    /** @var AttendeeValidator */
    protected $validator;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new AttendeeValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testValidValues($value)
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($value, new Attendee());
    }

    public function validValuesProvider()
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

    /**
     * @dataProvider testInvalidValuesProvider
     */
    public function testInvalidValues($value)
    {
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('Email or display name have to be specified.');

        $this->validator->validate($value, new Attendee());
    }

    public function testInvalidValuesProvider()
    {
        return [
            [(new AttendeeEntity())->setCalendarEvent(new CalendarEvent(1))]
        ];
    }
}
