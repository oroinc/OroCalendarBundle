<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Validator\Constraints\UniqueUid;
use Oro\Bundle\CalendarBundle\Validator\Constraints\UniqueUidValidator;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueUidValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    protected function setUp(): void
    {
        $this->mockDoctrine();
        parent::setUp();
    }

    public function testDoNotValidateIfUidIsNotProvided()
    {
        $constraint = new UniqueUid();

        $calendarEvent = new CalendarEvent();

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testDoNotValidateIfEventIsNotAParent()
    {
        $constraint = new UniqueUid();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid('123');
        $calendarEvent->setParent(new CalendarEvent());

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testDoNotValidateIfEventHasRecurringEvent()
    {
        $constraint = new UniqueUid();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid('123');
        $calendarEvent->setCalendar($this->getCalendarEntity(1));
        $calendarEvent->setRecurringEvent((new CalendarEvent())->setUid('123'));

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testDoNotValidateIfNoCalendarIsSpecified()
    {
        $constraint = new UniqueUid();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid('123');

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testNoValidationErrorsWhenAddingUniqueUid()
    {
        $this->repository->expects($this->once())
            ->method('findDuplicatedEvent')
            ->willReturn([]);

        $constraint = new UniqueUid();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid('123');
        $calendarEvent->setCalendar($this->getCalendarEntity(1));

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testValidationErrorWhenTryingToAddNewParentEventWithTheSameUidAndTheSameCalendar()
    {
        $this->repository->expects($this->once())
            ->method('findDuplicatedEvent')
            ->willReturn([new CalendarEvent()]);

        $constraint = new UniqueUid();

        $calendarEvent = new CalendarEvent();
        $calendarEvent->setUid('123');
        $calendarEvent->setCalendar($this->getCalendarEntity(1));

        $this->validator->validate($calendarEvent, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.uid')
            ->assertRaised();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new UniqueUidValidator($this->registry);
    }

    private function mockDoctrine()
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->repository = $this->createMock(CalendarEventRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->manager);
    }

    private function getCalendarEntity(int $id): Calendar
    {
        $calendar = new Calendar();
        $reflectionClass = new \ReflectionClass(Calendar::class);
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($calendar, $id);

        return $calendar;
    }
}
