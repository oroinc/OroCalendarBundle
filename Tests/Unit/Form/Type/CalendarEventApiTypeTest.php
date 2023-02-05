<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventApiType;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventAttendeesApiType;
use Oro\Bundle\CalendarBundle\Form\Type\RecurrenceFormType;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent as CalendarEventFixture;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsOptionsExtension;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class CalendarEventApiTypeTest extends FormIntegrationTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var CalendarEventManager|\PHPUnit\Framework\MockObject\MockObject */
    private $calendarEventManager;

    /** @var NotificationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationManager;

    /** @var CalendarEventApiType */
    private $calendarEventApiType;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
        $this->notificationManager = $this->createMock(NotificationManager::class);

        $userMeta = $this->createMock(ClassMetadata::class);
        $userMeta->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $eventMeta = $this->createMock(ClassMetadata::class);
        $eventMeta->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('execute')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->expects($this->any())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($qb);

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($userRepo);
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with('OroUserBundle:User')
            ->willReturn($userMeta);

        $emForEvent = $this->createMock(EntityManager::class);
        $emForEvent->expects($this->any())
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:CalendarEvent')
            ->willReturn($eventMeta);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($emForEvent);

        $this->calendarEventApiType = new CalendarEventApiType(
            $this->calendarEventManager,
            $this->notificationManager
        );

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects($this->any())
            ->method('getEntityName')
            ->willReturn('OroUserBundle:User');

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->willReturn($searchHandler);

        return [
            new PreloadedExtension(
                [
                    $this->calendarEventApiType,
                    new ReminderCollectionType(),
                    new CollectionType(),
                    new ReminderType(),
                    new MethodType(new SendProcessorRegistry([], $this->createMock(ContainerInterface::class))),
                    new ReminderIntervalType(),
                    new UnitType(),
                    new UserMultiSelectType($this->entityManager),
                    new OroJquerySelect2HiddenType(
                        $this->entityManager,
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    ),
                    new CalendarEventAttendeesApiType(),
                    new RecurrenceFormType(new Recurrence($this->createMock(StrategyInterface::class))),
                    new EntityIdentifierType($this->registry),
                ],
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

        $this->notificationManager->expects($this->any())
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
            ->with($this->isInstanceOf(CalendarEvent::class), Calendar::CALENDAR_ALIAS, 1);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        $this->assertInstanceOf(CalendarEvent::class, $result);
        $this->assertEquals('MOCK-UID-11111', $result->getUid());
        $this->assertEquals('testTitle', $result->getTitle());
        $this->assertEquals('testDescription', $result->getDescription());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        $this->assertTrue($result->getAllDay());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());

        $view = $form->createView();
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

        $this->notificationManager->expects($this->any())
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
            ->with($this->isInstanceOf(CalendarEvent::class), 'system', 1);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        $this->assertInstanceOf(CalendarEvent::class, $result);
        $this->assertNull($result->getUid());
        $this->assertEquals('testTitle', $result->getTitle());
        $this->assertEquals('testDescription', $result->getDescription());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        $this->assertTrue($result->getAllDay());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }

        $this->assertFalse($form->has('invitedUsers'));
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'      => CalendarEvent::class,
                'csrf_token_id'   => 'calendar_event',
                'csrf_protection' => false
            ]);

        $this->calendarEventApiType->configureOptions($resolver);
    }
}
