<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventApiType;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventAttendeesApiType;
use Oro\Bundle\CalendarBundle\Form\Type\RecurrenceFormType;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent as CalendarEventFixture;
use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsOptionsExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\ReminderBundle\Form\Type\MethodType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderCollectionType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderInterval\UnitType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderIntervalType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderType;
use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class CalendarEventApiTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendarEventManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $notificationManager;

    /** @var CalendarEventApiType */
    protected $calendarEventApiType;

    protected function setUp(): void
    {
        $this->registry = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarEventManager =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
                ->disableOriginalConstructor()
                ->getMock();

        $userMeta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $userMeta->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $eventMeta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $eventMeta->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('execute')
            ->will($this->returnValue([]));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('where')
            ->will($this->returnSelf());
        $qb->expects($this->any())
            ->method('setParameter')
            ->will($this->returnSelf());
        $qb->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $userRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepo->expects($this->any())
            ->method('createQueryBuilder')
            ->with('event')
            ->will($this->returnValue($qb));

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($userRepo));
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with('OroUserBundle:User')
            ->will($this->returnValue($userMeta));
        $emForEvent = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $emForEvent->expects($this->any())
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:CalendarEvent')
            ->will($this->returnValue($eventMeta));
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($emForEvent);

        $this->notificationManager = $this->getMockBuilder(NotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarEventApiType = new CalendarEventApiType(
            $this->calendarEventManager,
            $this->notificationManager
        );

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                $this->loadTypes(),
                [
                    TextType::class => [new DynamicFieldsOptionsExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmitValidData()
    {
        $formData = [
            'uid'             => 'MOCK-UID-11111',
            'calendar'        => 1,
            'title'           => 'testTitle',
            'description'     => 'testDescription',
            'start'           => '2013-10-05T13:00:00Z',
            'end'             => '2013-10-05T13:30:00+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'reminders'       => [],
            'attendees'       => [],
        ];

        $this->notificationManager
            ->expects($this->any())
            ->method('getSupportedStrategies')
            ->willReturn([]);

        $form = $this->factory->create(
            CalendarEventApiType::class,
            null,
            [
                'data_class' => CalendarEventFixture::class,
                'data' => new CalendarEventFixture()
            ]
        );

        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with(
                $this->isInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent'),
                Calendar::CALENDAR_ALIAS,
                1
            );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent', $result);
        $this->assertEquals('MOCK-UID-11111', $result->getUid());
        $this->assertEquals('testTitle', $result->getTitle());
        $this->assertEquals('testDescription', $result->getDescription());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        $this->assertTrue($result->getAllDay());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());

        $view     = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }

        $this->assertFalse($form->has('invitedUsers'));
    }

    public function testSubmitValidDataSystemCalendar()
    {
        $formData = [
            'calendar'        => 1,
            'calendarAlias'   => 'system',
            'title'           => 'testTitle',
            'description'     => 'testDescription',
            'start'           => '2013-10-05T13:00:00Z',
            'end'             => '2013-10-05T13:30:00+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'reminders'       => [],
        ];

        $this->notificationManager
            ->expects($this->any())
            ->method('getSupportedStrategies')
            ->willReturn([]);

        $form = $this->factory->create(
            CalendarEventApiType::class,
            null,
            [
                'data_class' => CalendarEventFixture::class,
                'data' => new CalendarEventFixture()
            ]
        );

        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with(
                $this->isInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent'),
                'system',
                1
            );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent', $result);
        $this->assertNull($result->getUid());
        $this->assertEquals('testTitle', $result->getTitle());
        $this->assertEquals('testDescription', $result->getDescription());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        $this->assertTrue($result->getAllDay());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());

        $view     = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }

        $this->assertFalse($form->has('invitedUsers'));
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'           => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                    'csrf_token_id'        => 'calendar_event',
                    'csrf_protection'      => false,
                ]
            );

        $this->calendarEventApiType->configureOptions($resolver);
    }

    /**
     * @return AbstractType[]
     */
    protected function loadTypes()
    {
        $searchHandler = $this->createMock('Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface');
        $searchHandler->expects($this->any())
            ->method('getEntityName')
            ->will($this->returnValue('OroUserBundle:User'));

        $searchRegistry = $this->getMockBuilder('Oro\Bundle\FormBundle\Autocomplete\SearchRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->will($this->returnValue($searchHandler));

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $strategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface')
            ->getMock();

        $recurrenceModel = new Recurrence($strategy);

        $types = [
            $this->calendarEventApiType,
            new ReminderCollectionType($this->registry),
            new CollectionType($this->registry),
            new ReminderType($this->registry),
            new MethodType(new SendProcessorRegistry([], $this->createMock(ContainerInterface::class))),
            new ReminderIntervalType(),
            new UnitType(),
            new UserMultiSelectType($this->entityManager),
            new OroJquerySelect2HiddenType($this->entityManager, $searchRegistry, $configProvider),
            new CalendarEventAttendeesApiType(),
            new RecurrenceFormType($recurrenceModel),
            new EntityIdentifierType($this->registry),
        ];

        $keys = array_map(
            function ($type) {
                /* @var AbstractType $type */
                return $type->getName();
            },
            $types
        );

        return array_combine($keys, $types);
    }
}
