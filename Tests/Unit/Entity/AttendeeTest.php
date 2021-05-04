<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AttendeeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            'id'            => ['id', 1],
            'user'          => ['user', $this->createMock(User::class)],
            'calendarEvent' => ['calendarEvent', $this->createMock(CalendarEvent::class)],
            'email'         => ['email', 'email@email.com'],
            'displayName'   => ['displayName', 'Display Name'],
            'createdAt'     => ['createdAt', new \DateTime()],
            'updatedAt'     => ['updatedAt', new \DateTime()],
        ];

        $entity = new Attendee();
        self::assertPropertyAccessors($entity, $properties);
    }

    public function testPrePersist()
    {
        $entity = new Attendee();
        $entity->beforeSave();

        self::assertNotNull($entity->getCreatedAt());
        self::assertNotNull($entity->getUpdatedAt());
        self::assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        self::assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());

        $existingCreatedAt = $entity->getCreatedAt();
        $existingUpdatedAt = $entity->getUpdatedAt();
        $entity->beforeSave();
        self::assertNotSame($existingCreatedAt, $entity->getCreatedAt());
        self::assertNotSame($existingUpdatedAt, $entity->getUpdatedAt());
        self::assertEquals($entity->getCreatedAt(), $entity->getUpdatedAt());
        self::assertNotSame($entity->getCreatedAt(), $entity->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $entity = new Attendee();
        $entity->preUpdate();

        self::assertNotNull($entity->getUpdatedAt());

        $existingUpdatedAt = $entity->getUpdatedAt();
        $entity->preUpdate();
        self::assertNotSame($existingUpdatedAt, $entity->getUpdatedAt());
    }

    public function testToString()
    {
        $entity = new Attendee();
        self::assertSame('', (string)$entity);

        $displayName = 'display name';
        $entity->setDisplayName($displayName);
        self::assertSame($displayName, (string)$entity);
    }
}
