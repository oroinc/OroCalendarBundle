<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\UserBundle\Entity\Email;

class AttendeeRelationManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /** @var User[] */
    protected $users;

    protected function setUp(): void
    {
        $this->users = [
            'u1@example.com' => (new User())->setEmail('u1@example.com'),
            'u2@example.com' => (new User())->addEmail((new Email())->setEmail('u2@example.com')),
            'u3@example.com' => (new User())->setEmail('u3@example.com'),
            'u4@example.com' => (new User())->setEmail('u4@example.com'),
        ];

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->any())
            ->method('findUsersByEmailsAndOrganization')
            ->will($this->returnCallback(function (array $emails) {
                return array_values(array_intersect_key($this->users, array_flip($emails)));
            }));

        $registry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $registry
            ->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($userRepository));

        $nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $nameFormatter->expects($this->any())
            ->method('format')
            ->will($this->returnCallback(function ($person) {
                return $person->getFullName();
            }));

        $dqlNameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

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

    public function setRelatedEntityDataProvider()
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
    public function testGetDisplayName($attendee, $expectedDisplayName)
    {
        $this->assertEquals($expectedDisplayName, $this->attendeeRelationManager->getDisplayName($attendee));
    }

    public function getDisplayNameProvider()
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

    protected function getInitialAttendees()
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

    protected function getExpectedAttendees()
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
