<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CalendarEventUid implements Migration, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return bool
     */
    protected function isMysqlPlatform()
    {
        return $this->platform->getName() === DatabasePlatformInterface::DATABASE_MYSQL;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_event');
        if (!$table->hasColumn('uid')) {
            $table->addColumn('uid', 'text', ['notnull' => false]);
        }
        if ($table->hasIndex('idx_2ddc40dda40a2c8')) {
            $table->dropIndex('idx_2ddc40dda40a2c8');
        }
        if (!$table->hasIndex('oro_calendar_event_uid_idx')) {
            if ($this->isMysqlPlatform()) {
                $queries->addPostQuery(
                    'ALTER TABLE `oro_calendar_event` ADD INDEX `oro_calendar_event_uid_idx` (calendar_id, uid(50))'
                );
            } else {
                $queries->addPostQuery(
                    'CREATE INDEX oro_calendar_event_uid_idx ON oro_calendar_event(calendar_id, uid);'
                );
            }
        }
    }
}
