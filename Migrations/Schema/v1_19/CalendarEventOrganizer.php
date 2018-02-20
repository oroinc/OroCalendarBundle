<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CalendarEventOrganizer implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_event');
        if (!$table->hasColumn('is_organizer')) {
            $table->addColumn('is_organizer', 'boolean', ['notnull' => false]);
        }
        if (!$table->hasColumn('organizer_user_id')) {
            $table->addColumn('organizer_user_id', 'integer', ['notnull' => false]);
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_user'),
                ['organizer_user_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'SET NULL']
            );
        }
        if (!$table->hasColumn('organizer_email')) {
            $table->addColumn('organizer_email', 'string', ['notnull' => false, 'length' => 255]);
        }
        if (!$table->hasColumn('organizer_display_name')) {
            $table->addColumn('organizer_display_name', 'string', ['notnull' => false, 'length' => 255]);
        }
    }
}
