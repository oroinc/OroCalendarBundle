<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Autocomplete\AttendeeSearchHandler;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AttendeeSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    private $indexer;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var AttendeeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attendeeManager;

    /** @var AttendeeSearchHandler */
    private $attendeeSearchHandler;

    protected function setUp(): void
    {
        $this->indexer = $this->createMock(Indexer::class);
        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->addMethods(['findById'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->attendeeManager = $this->createMock(AttendeeManager::class);

        $activityManager = $this->createMock(ActivityManager::class);
        $configManager = $this->createMock(ConfigManager::class);
        $entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $nameResolver = $this->createMock(EntityNameResolver::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id;
            });

        $om = $this->createMock(ObjectManager::class);
        $om->expects($this->any())
            ->method('getRepository')
            ->with('entity')
            ->willReturn($this->entityRepository);

        $this->attendeeSearchHandler = new AttendeeSearchHandler(
            $translator,
            $this->indexer,
            $activityManager,
            $configManager,
            $entityClassNameHelper,
            $om,
            $nameResolver,
            $dispatcher
        );
        $this->attendeeSearchHandler->setAttendeeManager($this->attendeeManager);
    }

    public function testSearch()
    {
        $items = [
            new Item('entity', 1),
            new Item('entity', 2),
        ];

        $users = [
            (new User(1))
                ->setEmail('user1@example.com')
                ->setFirstName('user1'),
            (new User(2))
                ->setEmail('user2@example.com')
                ->setFirstName('user2'),
        ];

        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with('query', 0, 101, ['oro_user'], 1)
            ->willReturn(new Result(new Query(), $items));

        $this->entityRepository->expects($this->once())
            ->method('findById')
            ->with([1, 2])
            ->willReturn($users);

        $this->attendeeManager->expects($this->exactly(2))
            ->method('createAttendee')
            ->withConsecutive(
                [$users[0]],
                [$users[1]]
            )
            ->willReturnCallback(function (User $user) {
                return (new Attendee())
                    ->setUser($user)
                    ->setDisplayName($user->getFirstName())
                    ->setEmail($user->getEmail())
                    ->setStatus(new TestEnumValue('test', 'test'))
                    ->setType(new TestEnumValue('test', 'test'));
            });

        $result = $this->attendeeSearchHandler->search('query', 1, 100);

        $this->assertEquals(
            [
                'results' => [
                    [
                        'id'          => json_encode(
                            ['entityClass' => User::class, 'entityId' => 1],
                            JSON_THROW_ON_ERROR
                        ),
                        'text'        => 'user1',
                        'displayName' => 'user1',
                        'email'       => 'user1@example.com',
                        'status'      => 'test',
                        'type'        => 'test',
                        'userId'      => 1,
                    ],
                    [
                        'id'          => json_encode(
                            ['entityClass' => User::class, 'entityId' => 2],
                            JSON_THROW_ON_ERROR
                        ),
                        'text'        => 'user2',
                        'displayName' => 'user2',
                        'email'       => 'user2@example.com',
                        'status'      => 'test',
                        'type'        => 'test',
                        'userId'      => 2,
                    ],
                ],
                'more'    => false,
            ],
            $result
        );
    }
}
