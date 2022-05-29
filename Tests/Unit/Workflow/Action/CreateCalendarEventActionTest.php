<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Workflow\Action;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Workflow\Action\CreateCalendarEventAction;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateCalendarEventActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    protected function setUp(): void
    {
        $calendarRepository = $this->createMock(CalendarRepository::class);

        $calendar = new Calendar();
        $calendar->setOwner($this->getUser());
        $calendarRepository->expects($this->any())
            ->method('findDefaultCalendar')
            ->willReturn($calendar);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturn($calendarRepository);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, int $expectedPersistCount, string $exceptionMessage, array $data = [])
    {
        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->exactly($expectedPersistCount))
            ->method('persist')
            ->willReturnCallback(function ($object) use ($options) {
                if (CalendarEvent::class === get_class($object)) {
                    $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_TITLE], $object->getTitle());
                    $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_START], $object->getStart());
                    if (isset($options[CreateCalendarEventAction::OPTION_KEY_END])) {
                        $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_END], $object->getEnd());
                    } elseif (isset($options[CreateCalendarEventAction::OPTION_KEY_DURATION])) {
                        $this->assertEquals(
                            $options[CreateCalendarEventAction::OPTION_KEY_START]
                                ->modify('+ ' . $options[CreateCalendarEventAction::OPTION_KEY_DURATION]),
                            $object->getEnd()
                        );
                    } else {
                        $this->assertEquals(
                            $options[CreateCalendarEventAction::OPTION_KEY_START]->modify('+ 1 hour'),
                            $object->getEnd()
                        );
                    }
                } elseif (Reminder::class === get_class($object)) {
                    $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_TITLE], $object->getSubject());
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        'Persistent object must be "%s" or "%s"',
                        CalendarEvent::class,
                        Reminder::class
                    ));
                }
            });
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        if ($exceptionMessage) {
            $this->expectException(InvalidParameterException::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $action = $this->getAction();
        $context = new ItemStub($data);
        $action->initialize($options);
        $action->execute($context);
    }

    public function executeDataProvider(): array
    {
        return [
            'without options' => [
                'options' => [],
                'expectedPersistCount' => 0,
                'exceptionMessage' => 'Required fields "title, initiator, start" must be filled',
            ],
            'only title' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                ],
                'expectedPersistCount' => 0,
                'exceptionMessage' => 'Required fields "initiator, start" must be filled',
            ],
            'only required options' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUser(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with end' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUser(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with duration' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUser(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_DURATION => '2 hour 30 minutes',
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with guests' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUser(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_GUESTS => new PropertyPath('data[guests]'),
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
                'data' => [
                    'guests' => [$this->getUser(), $this->getUser()],
                ],
            ],
            'with attribute' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUser(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_GUESTS => [$this->getUser(), $this->getUser()],
                    CreateCalendarEventAction::OPTION_KEY_ATTRIBUTE => 'attribute',
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with reminders' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUser(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_GUESTS => [$this->getUser(), $this->getUser()],
                    CreateCalendarEventAction::OPTION_KEY_REMINDERS => [[
                        CreateCalendarEventAction::OPTION_REMINDER_KEY_METHOD => 'email',
                        CreateCalendarEventAction::OPTION_REMINDER_KEY_INTERVAL_UNIT => 'H',
                        CreateCalendarEventAction::OPTION_REMINDER_KEY_INTERVAL_NUMBER => '1',
                        ],[
                        CreateCalendarEventAction::OPTION_REMINDER_KEY_METHOD => 'web_socket',
                        CreateCalendarEventAction::OPTION_REMINDER_KEY_INTERVAL_UNIT => 'M',
                        CreateCalendarEventAction::OPTION_REMINDER_KEY_INTERVAL_NUMBER => '10',
                        ]
                    ]
                ],
                'expectedPersistCount' => 7,
                'exceptionMessage' => '',
            ],
        ];
    }

    private function getAction(): CreateCalendarEventAction
    {
        $action = new CreateCalendarEventAction(new ContextAccessor(), $this->doctrine);
        $dispatcher = $this->createMock(EventDispatcher::class);
        $action->setDispatcher($dispatcher);

        return $action;
    }

    private function getUser(): User
    {
        $organization = $this->createMock(Organization::class);
        $organization->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $user = $this->createMock(User::class);
        $user->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $user->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        return $user;
    }
}
