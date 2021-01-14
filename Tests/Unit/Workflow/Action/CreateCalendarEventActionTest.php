<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Workflow\Action\CreateCalendarEventAction;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateCalendarEventActionTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME_CALENDAR_EVENT = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';
    const CLASS_NAME_REMINDER       = 'Oro\Bundle\ReminderBundle\Entity\Reminder';

    /**
     * @var ContextAccessor
     */
    private $contextAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();

        $calendarRepository = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findDefaultCalendar'])
            ->getMock();

        $calendar = new Calendar();
        $calendar->setOwner($this->getUserMock());
        $calendarRepository->method('findDefaultCalendar')->willReturn($calendar);
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManagerForClass', 'getManager', 'getRepository'])
            ->getMock();
        $this->registry->method('getRepository')->willReturn($calendarRepository);
    }

    protected function tearDown(): void
    {
        unset($this->contextAccessor);
        unset($this->registry);
        unset($this->action);
    }

    /**
     * @param array $options
     * @param int $expectedPersistCount
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, $expectedPersistCount, $exceptionMessage, $data = [])
    {
        $em = $this->getMockBuilder('\Doctrine\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em
            ->expects($this->exactly($expectedPersistCount))
            ->method('persist')
            ->will($this->returnCallback(function ($object) use ($options) {
                if ('Oro\Bundle\CalendarBundle\Entity\CalendarEvent' === get_class($object)) {
                    $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_TITLE], $object->getTitle());
                    $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_START], $object->getStart());
                    if (isset($options[CreateCalendarEventAction::OPTION_KEY_END])) {
                        $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_END], $object->getEnd());
                    } elseif (isset($options[CreateCalendarEventAction::OPTION_KEY_DURATION])) {
                        $this->assertEquals(
                            $options[CreateCalendarEventAction::OPTION_KEY_START]
                                ->modify('+ '.$options[CreateCalendarEventAction::OPTION_KEY_DURATION]),
                            $object->getEnd()
                        );
                    } else {
                        $this->assertEquals(
                            $options[CreateCalendarEventAction::OPTION_KEY_START]->modify('+ 1 hour'),
                            $object->getEnd()
                        );
                    }
                } elseif ('Oro\Bundle\ReminderBundle\Entity\Reminder' === get_class($object)) {
                    $this->assertEquals($options[CreateCalendarEventAction::OPTION_KEY_TITLE], $object->getSubject());
                } else {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Persistent object must be "%s" or "%s"',
                            self::CLASS_NAME_CALENDAR_EVENT,
                            self::CLASS_NAME_REMINDER
                        )
                    );
                }
            }))
        ;
        $this->registry->method('getManagerForClass')->willReturn($em);

        if ($exceptionMessage) {
            $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $action = $this->getAction();
        $context = new ItemStub($data);
        $action->initialize($options);
        $action->execute($context);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
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
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUserMock(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with end' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUserMock(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with duration' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUserMock(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_DURATION => '2 hour 30 minutes',
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with guests' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUserMock(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_GUESTS => new PropertyPath('data[guests]'),
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
                'data' => [
                    'guests' => [$this->getUserMock(), $this->getUserMock()],
                ],
            ],
            'with attribute' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUserMock(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_GUESTS => [$this->getUserMock(), $this->getUserMock()],
                    CreateCalendarEventAction::OPTION_KEY_ATTRIBUTE => 'attribute',
                ],
                'expectedPersistCount' => 1,
                'exceptionMessage' => '',
            ],
            'with reminders' => [
                'options' => [
                    CreateCalendarEventAction::OPTION_KEY_TITLE => 'Title',
                    CreateCalendarEventAction::OPTION_KEY_INITIATOR => $this->getUserMock(),
                    CreateCalendarEventAction::OPTION_KEY_START => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_END => new \DateTime(),
                    CreateCalendarEventAction::OPTION_KEY_GUESTS => [$this->getUserMock(), $this->getUserMock()],
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

    /**
     * @return CreateCalendarEventAction
     */
    protected function getAction()
    {
        $action = new CreateCalendarEventAction($this->contextAccessor, $this->registry);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $action->setDispatcher($dispatcher);

        return $action;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getUserMock()
    {
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $organization->method('getId')->willReturn(1);
        $user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getOrganization'])
            ->getMock();
        $user->method('getId')->willReturn(1);
        $user->method('getOrganization')->willReturn($organization);

        return $user;
    }
}
