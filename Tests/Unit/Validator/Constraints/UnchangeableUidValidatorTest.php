<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\CalendarBundle\Validator\Constraints\UnchangeableUid;
use Oro\Bundle\CalendarBundle\Validator\Constraints\UnchangeableUidValidator;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Component\Testing\Validator\AbstractConstraintValidatorTest;

class UnchangeableUidValidatorTest extends AbstractConstraintValidatorTest
{
    /** @var Query|\PHPUnit_Framework_MockObject_MockObject */
    private $query;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    protected function setUp()
    {
        $this->mockDoctrine();
        parent::setUp();
    }

    /**
     * Means: do not validate for new events (as they do not have old value of UID)
     */
    public function testDoNotValidateIfEventDoesNotHaveId()
    {
        $constraint = new UnchangeableUid();

        $calendarEvent = new CalendarEvent();

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testDoNotValidateIfEventDoesNotHaveUid()
    {
        $constraint = new UnchangeableUid();

        $calendarEvent = $this->getCalendarEventEntity(1);

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testDoNotValidateIfEventDidNotHaveUidPreviouslySet()
    {
        $constraint = new UnchangeableUid();

        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->setUid('UUID-123');

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testNoValidationErrorWhenOldAndNewUidAreTheSame()
    {
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(['uid' => 'UUID-123']);

        $constraint = new UnchangeableUid();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->setUid('UUID-123');

        $this->validator->validate($calendarEvent, $constraint);

        $this->assertNoViolation();
    }

    public function testValidationErrorWhenTryingToChangeUidOfEvent()
    {
        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(['uid' => '123']);

        $constraint = new UnchangeableUid();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->setUid('UUID-123');

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
        return new UnchangeableUidValidator($this->registry);
    }

    private function mockDoctrine()
    {
        $this->query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->repository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->manager);
    }

    /**
     * @param int $id
     * @return CalendarEvent
     */
    private function getCalendarEventEntity(int $id): CalendarEvent
    {
        $calendarEvent = new CalendarEvent();
        $reflectionClass = new \ReflectionClass(CalendarEvent::class);
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($calendarEvent, $id);

        return $calendarEvent;
    }
}
