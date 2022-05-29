<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Validator\Constraints\EventAttendees;
use Oro\Bundle\CalendarBundle\Validator\Constraints\EventAttendeesValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EventAttendeesValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AttendeeRepository::class);
        parent::setUp();
    }

    /**
     * Means: do not validate for new events (as they have no attendees)
     */
    public function testNoViolationIfEventDoesNotHaveId()
    {
        $constraint = new EventAttendees();
        $calendarEvent = new CalendarEvent();
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationIfEventIsOrganizer()
    {
        $constraint = new EventAttendees();
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setIsOrganizer(true);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationIfAttendeeListIsEqual()
    {
        $attendee1 = new Attendee();
        $attendee2 = new Attendee();
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->addAttendee($attendee1)->addAttendee($attendee2);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee1, $attendee2]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationIfAttendeeListIsEqualDespiteAttendeesOrder()
    {
        $attendee1 = $this->getCalendarEventAttendeeEntity(1, 'test1@oroinc.com');
        $attendee2 = $this->getCalendarEventAttendeeEntity(2, 'test2@oroinc.com');
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->addAttendee($attendee1)->addAttendee($attendee2);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee2, $attendee1]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationWhenTryingToChangeAttendeesOnOrganizerEvent()
    {
        $attendee1 = $this->getCalendarEventAttendeeEntity(1, 'test1@oroinc.com');
        $attendee2 = $this->getCalendarEventAttendeeEntity(2, 'test2@oroinc.com');
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->setIsOrganizer(true);
        $calendarEvent->addAttendee($attendee1);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee2, $attendee1]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testViolationWhenTryingToChangeAttendeesOnNonOrganizerEvent()
    {
        $attendee1 = $this->getCalendarEventAttendeeEntity(1, 'test1@oroinc.com');
        $attendee2 = $this->getCalendarEventAttendeeEntity(2, 'test2@oroinc.com');
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->setIsOrganizer(false);
        $calendarEvent->addAttendee($attendee1);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee2, $attendee1]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('property.path.attendees')
            ->assertRaised();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        return new EventAttendeesValidator($doctrine);
    }

    private function getCalendarEventEntity(int $id): CalendarEvent
    {
        $calendarEvent = new CalendarEvent();
        ReflectionUtil::setId($calendarEvent, $id);

        return $calendarEvent;
    }

    private function getCalendarEventAttendeeEntity(int $id, string $email): Attendee
    {
        $attendee = new Attendee();
        ReflectionUtil::setId($attendee, $id);
        $attendee->setEmail($email);

        return $attendee;
    }
}
