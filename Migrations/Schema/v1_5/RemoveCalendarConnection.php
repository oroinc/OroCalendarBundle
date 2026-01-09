<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migration\DeleteAclMigrationQuery;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class RemoveCalendarConnection implements
    Migration,
    OrderedMigrationInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getOrder()
    {
        return 2;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('oro_calendar_connection');

        $calendarConnectionClass = 'Oro\Bundle\CalendarBundle\Entity\CalendarConnection';
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_field WHERE entity_id IN ('
                . 'SELECT id FROM oro_entity_config WHERE class_name = :class)',
                ['class' => $calendarConnectionClass],
                ['class' => 'string']
            )
        );
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class',
                ['class' => $calendarConnectionClass],
                ['class' => 'string']
            )
        );
        $queries->addPostQuery(
            new DeleteAclMigrationQuery(
                $this->container,
                new ObjectIdentity('entity', $calendarConnectionClass)
            )
        );
    }
}
