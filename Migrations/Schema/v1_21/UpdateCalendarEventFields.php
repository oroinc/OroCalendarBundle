<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_21;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * - Change UID type from text to guid
 * - Re-create oro_calendar_event_uid_idx for MySQL because of field type change
 * - Set default value for all_day and is_cancelled fields, make fields NOT NULL
 */
class UpdateCalendarEventFields implements Migration, DatabasePlatformAwareInterface, OrderedMigrationInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->fixUidField($schema, $queries);
        $this->setDefaultValueForBoolean('all_day', $schema, $queries);
        $this->setDefaultValueForBoolean('is_cancelled', $schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    private function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();

        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function setDefaultValueForBoolean(string $field, Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                sprintf('UPDATE oro_calendar_event SET %1$s=:field_value WHERE %1$s IS NULL', $field),
                ['field_value' => false],
                ['field_value' => 'boolean']
            )
        );

        $postSchema = clone $schema;
        $postSchema->getTable('oro_calendar_event')
            ->changeColumn($field, ['notnull' => true, 'default' => false]);
        $postQueries = $this->getSchemaDiff($schema, $postSchema);
        foreach ($postQueries as $query) {
            $queries->addPostQuery($query);
        }

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                CalendarEvent::class,
                $field,
                'extend',
                'nullable',
                false
            )
        );
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                CalendarEvent::class,
                $field,
                'extend',
                'default',
                false
            )
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function fixUidField(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_event');
        if ($this->platform instanceof MySqlPlatform) {
            if ($table->hasIndex('oro_calendar_event_uid_idx')) {
                $table->dropIndex('oro_calendar_event_uid_idx');
            } else {
                $queries->addPostQuery('ALTER TABLE `oro_calendar_event` DROP INDEX `oro_calendar_event_uid_idx`');
            }
        }

        $table->getColumn('uid')
            ->setType(Type::getType(Types::STRING))
            ->setLength(36);

        // Index will bre re-created by UpdateCalendarEventIndexes migration
    }
}
