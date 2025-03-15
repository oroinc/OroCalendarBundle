<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Doctrine\Common\EventManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
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
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsOptionsExtension;
use Oro\Bundle\EntityExtendBundle\Form\Guesser\ExtendFieldTypeGuesser;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
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
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class CalendarEventApiTypeTest extends FormIntegrationTestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private CalendarEventManager&MockObject $calendarEventManager;
    private NotificationManager&MockObject $notificationManager;
    private CalendarEventApiType $calendarEventApiType;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->calendarEventManager = $this->createMock(CalendarEventManager::class);
        $this->notificationManager = $this->createMock(NotificationManager::class);

        $userMeta = $this->createMock(ClassMetadata::class);
        $userMeta->expects(self::any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $eventMeta = $this->createMock(ClassMetadata::class);
        $eventMeta->expects(self::any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::any())
            ->method('execute')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::any())
            ->method('where')
            ->willReturnSelf();
        $qb->expects(self::any())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->expects(self::any())
            ->method('createQueryBuilder')
            ->with('event')
            ->willReturn($qb);

        $enumOptionRepo = $this->createMock(EnumOptionRepository::class);
        $enumOptionRepo->expects(self::any())
            ->method('getValuesQueryBuilder')
            ->willReturn($qb);

        $eventManager = $this->createMock(EventManager::class);
        $eventManager->expects(self::any())
            ->method('getAllListeners')
            ->willReturn([[$this->createMock(TranslatableListener::class)]]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepo);
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturnMap([
                [User::class, $userMeta],
                [CalendarEvent::class, $eventMeta]
            ]);
        $entityManager->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class));
        $entityManager->expects(self::any())
            ->method('getEventManager')
            ->willReturn($eventManager);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($enumOptionRepo);
        $this->doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($entityManager);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->calendarEventApiType = new CalendarEventApiType(
            $this->calendarEventManager,
            $this->notificationManager
        );

        parent::setUp();
    }

    #[\Override]
    protected function getTypeGuessers(): array
    {
        $extendTypeGuesser = $this->createMock(ExtendFieldTypeGuesser::class);
        $attendeeClass = Attendee::class;
        $attendeeEnumFields = [
            'status' => Attendee::STATUS_ENUM_CODE,
            'type' => Attendee::TYPE_ENUM_CODE
        ];
        $extendTypeGuesser->expects(self::any())
            ->method('guessType')
            ->willReturnCallback(
                fn ($class, $field) => $class === $attendeeClass && array_key_exists($field, $attendeeEnumFields)
                    ? new TypeGuess(
                        EnumSelectType::class,
                        ['enum_code' => $attendeeEnumFields[$field]],
                        TypeGuess::HIGH_CONFIDENCE
                    )
                    : null
            );

        return [$extendTypeGuesser];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects(self::any())
            ->method('getEntityName')
            ->willReturn(User::class);

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects(self::any())
            ->method('getSearchHandler')
            ->willReturn($searchHandler);
        $configManager = $this->createMock(ConfigManager::class);
        $configProvider = $this->createMock(ConfigProvider::class);
        $config = $this->createMock(ConfigInterface::class);
        $configManager->expects(self::any())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($configProvider);
        $configProvider->expects(self::any())
            ->method('getConfig')
            ->willReturn($config);
        $config->expects(self::any())
            ->method('is')
            ->with('multiple')
            ->willReturn(false);

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
                    new UserMultiSelectType($this->doctrine),
                    new EnumSelectType($configManager, $this->doctrine),
                    new TranslatableEntityType(
                        $this->doctrine,
                        $this->createMock(ChoiceListFactoryInterface::class),
                        $this->createMock(AclHelper::class)
                    ),
                    new OroJquerySelect2HiddenType(
                        $this->doctrine,
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    ),
                    new CalendarEventAttendeesApiType(),
                    new RecurrenceFormType(new Recurrence($this->createMock(StrategyInterface::class))),
                    new EntityIdentifierType($this->doctrine),
                ],
                [
                    TextType::class => [new DynamicFieldsOptionsExtension()],
                    EnumSelectType::class => [new DynamicFieldsOptionsExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSubmitValidData()
    {
        $formData = [
            'uid' => 'MOCK-UID-11111',
            'calendar' => 1,
            'title' => 'testTitle',
            'description' => 'testDescription',
            'start' => '2013-10-05T13:00:00Z',
            'end' => '2013-10-05T13:30:00+00:00',
            'createdAt' => '2013-10-05T11:00:00Z',
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'reminders' => [],
            'attendees' => [],
        ];

        $this->notificationManager->expects(self::any())
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

        $this->calendarEventManager->expects(self::once())
            ->method('setCalendar')
            ->with($this->isInstanceOf(CalendarEvent::class), Calendar::CALENDAR_ALIAS, 1);

        $form->submit(array_merge($formData, [
            'updatedAt' => '2013-10-05T11:30:00+00:00',
        ]));

        self::assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        self::assertInstanceOf(CalendarEvent::class, $result);
        self::assertEquals('MOCK-UID-11111', $result->getUid());
        self::assertEquals('testTitle', $result->getTitle());
        self::assertEquals('testDescription', $result->getDescription());
        self::assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        self::assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        self::assertDateTimeEquals(new \DateTime('2013-10-05T11:00:00Z'), $result->getCreatedAt());
        self::assertNull($result->getUpdatedAt());
        self::assertTrue($result->getAllDay());
        self::assertEquals('#FF0000', $result->getBackgroundColor());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }

        self::assertArrayNotHasKey('updatedAt', $children);

        self::assertFalse($form->has('invitedUsers'));
    }

    public function testSubmitValidDataSystemCalendar()
    {
        $formData = [
            'calendar' => 1,
            'calendarAlias' => 'system',
            'title' => 'testTitle',
            'description' => 'testDescription',
            'start' => '2013-10-05T13:00:00Z',
            'end' => '2013-10-05T13:30:00+00:00',
            'createdAt' => '2013-10-05T11:00:00Z',
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'reminders' => [],
        ];

        $this->notificationManager->expects(self::any())
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

        $this->calendarEventManager->expects(self::once())
            ->method('setCalendar')
            ->with($this->isInstanceOf(CalendarEvent::class), 'system', 1);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        self::assertInstanceOf(CalendarEvent::class, $result);
        self::assertNull($result->getUid());
        self::assertEquals('testTitle', $result->getTitle());
        self::assertEquals('testDescription', $result->getDescription());
        self::assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        self::assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        self::assertDateTimeEquals(new \DateTime('2013-10-05T11:00:00Z'), $result->getCreatedAt());
        self::assertNull($result->getUpdatedAt());
        self::assertTrue($result->getAllDay());
        self::assertEquals('#FF0000', $result->getBackgroundColor());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }

        self::assertFalse($form->has('invitedUsers'));
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with([
                'data_class' => CalendarEvent::class,
                'csrf_token_id' => 'calendar_event',
                'csrf_protection' => false
            ]);

        $this->calendarEventApiType->configureOptions($resolver);
    }
}
