<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Provider\CalendarPropertyProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;

class CalendarPropertyProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CalendarPropertyProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany']);

        $this->provider = new CalendarPropertyProvider(
            $this->doctrineHelper,
            $this->configManager,
            new FieldTypeHelper($entityExtendConfigurationProvider)
        );
    }

    private function getFieldConfig(string $fieldName, string $fieldType, array $values = []): Config
    {
        return new Config(
            new FieldConfigId('extend', CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS, $fieldName, $fieldType),
            $values
        );
    }

    public function testGetFields()
    {
        $fieldConfigs = [
            $this->getFieldConfig('id', 'integer'),
            $this->getFieldConfig('targetCalendar', 'ref-one'),
            $this->getFieldConfig('visible', 'boolean'),
            $this->getFieldConfig('many2one', 'manyToOne', ['is_extend' => true]),
            $this->getFieldConfig('many2many', 'manyToMany', ['is_extend' => true]),
            $this->getFieldConfig('one2many', 'oneToMany', ['is_extend' => true]),
            $this->getFieldConfig('enum', 'enum', ['is_extend' => true]),
            $this->getFieldConfig('multiEnum', 'multiEnum', ['is_extend' => true]),
            $this->getFieldConfig('new', 'string', ['state' => ExtendScope::STATE_NEW, 'is_extend' => true]),
            $this->getFieldConfig('deleted', 'string', ['is_deleted' => true, 'is_extend' => true]),
            $this->getFieldConfig(
                'new_to_be_deleted',
                'string',
                ['state' => ExtendScope::STATE_DELETE, 'is_extend' => true]
            ),
        ];

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('extend', CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($fieldConfigs);

        $result = $this->provider->getFields();
        $this->assertEquals(
            [
                'id'             => 'integer',
                'targetCalendar' => 'ref-one',
                'visible'        => 'boolean',
                'many2one'       => 'manyToOne',
                'enum'           => 'enum',
            ],
            $result
        );
    }

    public function testGetDefaultValues()
    {
        $fieldConfigs = [
            $this->getFieldConfig('id', 'integer'),
            $this->getFieldConfig('targetCalendar', 'ref-one'),
            $this->getFieldConfig('visible', 'boolean'),
            $this->getFieldConfig('enum', 'enum'),
        ];

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('extend', CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($fieldConfigs);

        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with(CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($metadata);

        $metadata->expects($this->exactly(count($fieldConfigs)))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['targetCalendar', false],
                ['visible', true],
                ['enum', false],
            ]);
        $metadata->expects($this->exactly(2))
            ->method('getFieldMapping')
            ->willReturnMap([
                ['id', []],
                ['visible', ['options' => ['default' => true]]],
            ]);

        $result = $this->provider->getDefaultValues();
        $this->assertEquals(
            [
                'id'             => null,
                'targetCalendar' => null,
                'visible'        => true,
                'enum'           => [$this->provider, 'getEnumDefaultValue'],
            ],
            $result
        );
    }

    /**
     * @dataProvider getEnumDefaultValueProvider
     */
    public function testGetEnumDefaultValue(array $defaults, ?string $expected)
    {
        $fieldName = 'test_enum';
        $fieldConfig = $this->getFieldConfig($fieldName, 'enum', ['target_entity' => 'Test\Enum']);

        $this->configManager->expects($this->once())
            ->method('getConfig')
            ->with($fieldConfig->getId())
            ->willReturn($fieldConfig);

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Test\Enum')
            ->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('e.id')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('e.default = true')
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($defaults);

        $this->assertSame(
            $expected,
            $this->provider->getEnumDefaultValue($fieldName)
        );
    }

    public function getEnumDefaultValueProvider(): array
    {
        return [
            [
                'defaults' => [],
                'expected' => null
            ],
            [
                'defaults' => [['id' => 'opt1']],
                'expected' => 'opt1'
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetItems()
    {
        $calendarId = 123;

        $fieldConfigs = [
            $this->getFieldConfig('id', 'integer'),
            $this->getFieldConfig('targetCalendar', 'ref-one'),
            $this->getFieldConfig('visible', 'boolean'),
            $this->getFieldConfig('enum', 'enum'),
        ];

        $items = [
            [
                'id'             => 1,
                'targetCalendar' => '123',
                'visible'        => true,
                'enum'           => 'opt1',
            ]
        ];

        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('extend', CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($fieldConfigs);

        $metadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->with(CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($metadata);

        $metadata->expects($this->exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['targetCalendar', true],
                ['enum', true],
            ]);
        $metadata->expects($this->exactly(2))
            ->method('getAssociationTargetClass')
            ->willReturnMap([
                ['targetCalendar', Calendar::class],
                ['enum', 'Test\Enum'],
            ]);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getSingleEntityIdentifierFieldType')
            ->willReturnMap([
                [Calendar::class, false, 'integer'],
                ['Test\Enum', false, 'string'],
            ]);

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('o.id,IDENTITY(o.targetCalendar) AS targetCalendar,o.visible,IDENTITY(o.enum) AS enum')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.targetCalendar = :calendar_id')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('calendar_id', $calendarId)
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('orderBy')
            ->with('o.id')
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($items);

        $result = $this->provider->getItems($calendarId);
        $this->assertSame(
            [
                [
                    'id'             => 1,
                    'targetCalendar' => 123,
                    'visible'        => true,
                    'enum'           => 'opt1',
                ]
            ],
            $result
        );
    }

    public function testGetItemsVisibility()
    {
        $calendarId = 123;
        $subordinate = true;
        $items = [['calendarAlias' => 'test', 'calendar' => 1, 'visible' => true]];

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('o.calendarAlias, o.calendar, o.visible')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.targetCalendar = :calendar_id')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('calendar_id', $calendarId)
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($items);

        $result = $this->provider->getItemsVisibility($calendarId, $subordinate);
        $this->assertSame($items, $result);
    }

    public function testGetItemsVisibilityCurrentCalendarOnly()
    {
        $calendarId = 123;
        $subordinate = false;
        $items = [['calendarAlias' => 'test', 'calendar' => 1, 'visible' => true]];

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CalendarPropertyProvider::CALENDAR_PROPERTY_CLASS)
            ->willReturn($repo);
        $qb = $this->createMock(QueryBuilder::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('select')
            ->with('o.calendarAlias, o.calendar, o.visible')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('o.targetCalendar = :calendar_id')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('o.calendarAlias = :alias AND o.calendar = :calendar_id')
            ->willReturnSelf();
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['calendar_id', $calendarId],
                ['alias', Calendar::CALENDAR_ALIAS]
            )
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($items);

        $result = $this->provider->getItemsVisibility($calendarId, $subordinate);
        $this->assertSame($items, $result);
    }
}
