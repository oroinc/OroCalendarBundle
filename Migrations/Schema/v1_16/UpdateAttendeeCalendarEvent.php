<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateAttendeeCalendarEvent implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_calendar_event_attendee');
        $table->modifyColumn('calendar_event_id', ['notnull' => false]);
    }
}
