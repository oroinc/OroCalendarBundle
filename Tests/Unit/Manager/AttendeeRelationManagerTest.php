<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class AttendeeRelationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var User[] */
    private $users;

    /** @var AttendeeRelationManager */
    private $attendeeRelationManager;

    protected function setUp(): void
    {
        $this->users = [
            'u1@example.com' => (new User())->setEmail('u1@example.com'),
            'u2@example.com' => (new User())->addEmail((new Email())->setEmail('u2@example.com')),
            'u3@example.com' => (new User())->setEmail('u3@example.com'),
            'u4@example.com' => (new User())->setEmail('u4@example.com'),
        ];

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->any())
            ->method('findUsersByEmailsAndOrganization')
            ->willReturnCallback(function (array $emails) {
                return array_values(array_intersect_key($this->users, array_flip($emails)));
            });

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($userRepository);

        $nameFormatter = $this->createMock(NameFormatter::class);
        $nameFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(function ($person) {
                return $person->getFullName();
            });

        $dqlNameFormatter = $this->createMock(DQLNameFormatter::class);

        $this->attendeeRelationManager = new AttendeeRelationManager(
            $registry,
            $nameFormatter,
            $dqlNameFormatter
        );
    }

    public function testSetRelatedEntityWithUserWorks()
    {
        $attendee = new Attendee();

        $user = new User();
        $user
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@example.com');

        $this->attendeeRelationManager->setRelatedEntity($attendee, $user);

        $this->assertEquals('John Doe', $attendee->getDisplayName());
        $this->assertEquals('john.doe@example.com', $attendee->getEmail());
        $this->assertSame($user, $attendee->getUser());
    }

    public function testSetRelatedEntityWithIncorrectTypeFails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Related entity must be an instance of "Oro\Bundle\UserBundle\Entity\User", "stdClass" is given.'
        );

        $attendee = new Attendee();

        $entity = new \stdClass();

        $this->attendeeRelationManager->setRelatedEntity($attendee, $entity);
    }

    public function setRelatedEntityDataProvider(): array
    {
        $attendee = new Attendee();
        $user = (new User())
            ->setFirstName('first')
            ->setLastName('last')
            ->setEmail('email@example.com');

        return [
            [
                null,
                $attendee
            ],
            [
                $user,
                $attendee,
                (new AttendeeEntity())
                    ->setDisplayName('first last')
                    ->setEmail('email@example.com')
                    ->setUser($user),
            ]
        ];
    }

    public function testGetRelatedEntity()
    {
        $user = new User();
        $attendee = (new Attendee())
            ->setUser($user);

        $this->assertSame($user, $this->attendeeRelationManager->getRelatedEntity($attendee));
    }

    /**
     * @dataProvider getDisplayNameProvider
     */
    public function testGetDisplayName(Attendee $attendee, string $expectedDisplayName)
    {
        $this->assertEquals($expectedDisplayName, $this->attendeeRelationManager->getDisplayName($attendee));
    }

    public function getDisplayNameProvider(): array
    {
        return [
            [
                (new Attendee())
                    ->setDisplayName('display name'),
                'display name'
            ],
            [
                (new Attendee())
                    ->setUser(
                        (new User())
                            ->setFirstName('first')
                            ->setLastName('last')
                    ),
                'first last'
            ],
            [
                (new Attendee())
                    ->setDisplayName('display name')
                    ->setUser(
                        (new User())
                            ->setFirstName('first')
                            ->setLastName('last')
                    ),
                'first last'
            ],
        ];
    }

    public function testBindAttendees()
    {
        $attendees = $this->getInitialAttendees();
        $this->attendeeRelationManager->bindAttendees($attendees);

        $this->assertEquals($this->getExpectedAttendees(), $attendees);
    }

    private function getInitialAttendees(): array
    {
        return [
            (new Attendee(1))
                ->setEmail('u1@example.com'),
            (new Attendee())
                ->setEmail('u2@example.com'),
            (new Attendee())
                ->setEmail('u3@example.com'),
            (new Attendee())
                ->setEmail('nonExisting@example.com'),
            (new Attendee())
                ->setEmail('u4@example.com')
                ->setUser(new User()),
        ];
    }

    private function getExpectedAttendees(): array
    {
        return [
            (new Attendee(1))
                ->setEmail('u1@example.com')
                 ->setUser($this->users['u1@example.com'])
                 ->setDisplayName($this->users['u1@example.com']->getFullName()),
            (new Attendee())
                ->setEmail('u2@example.com')
                ->setUser($this->users['u2@example.com'])
                ->setDisplayName($this->users['u2@example.com']->getFullName()),
            (new Attendee())
                ->setEmail('u3@example.com')
                ->setUser($this->users['u3@example.com'])
                ->setDisplayName($this->users['u3@example.com']->getFullName()),
            (new Attendee())
                ->setEmail('nonExisting@example.com'),
            (new Attendee())
                ->setEmail('u4@example.com')
                ->setUser(new User()),
        ];
    }
}
