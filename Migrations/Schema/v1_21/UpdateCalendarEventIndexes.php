<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_21;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * - Re-create oro_calendar_event_uid_idx for MySQL because of field type change
 */
class UpdateCalendarEventIndexes implements Migration, DatabasePlatformAwareInterface, OrderedMigrationInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // Re-create index only for MySQL
        if (!$this->platform instanceof MySqlPlatform) {
            return;
        }

        $table = $schema->getTable('oro_calendar_event');
        if (!$table->hasIndex('oro_calendar_event_uid_idx')) {
            $table->addIndex(['calendar_id', 'uid'], 'oro_calendar_event_uid_idx');
        }
    }
}
