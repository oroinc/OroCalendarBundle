<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveInvitationStatusFieldConfig implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new RemoveInvitationStatusFieldConfigQuery());
    }
}
