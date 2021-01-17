<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveSystemCalendarAcl extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->updateSystemCalendarAcl($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateSystemCalendarAcl($logger);
    }

    /**
     * Remove SystemCalendar ACL
     *
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateSystemCalendarAcl(LoggerInterface $logger, $dryRun = false)
    {
        $query  = 'SELECT c.id, c.data FROM oro_entity_config c'
            . ' WHERE c.class_name = :class_name AND c.mode = :mode';
        $params = [
            'class_name' => 'Oro\\Bundle\\CalendarBundle\\Entity\\SystemCalendar',
            'mode'       => ConfigModel::MODE_DEFAULT
        ];
        $types  = [
            'class_name' => Types::STRING,
            'mode'       => Types::STRING
        ];
        $this->logQuery($logger, $query, $params, $types);

        // prepare update query
        $rows = $this->connection->fetchAll($query, $params, $types);
        if (count($rows) > 0) {
            $row = $rows[0];
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (isset($data['security'])) {
                unset($data['security']);
                $id = $row['id'];
                $updateQuery = [
                    'UPDATE oro_entity_config SET data = :data WHERE id = :id',
                    ['id' => $id, 'data' => $data],
                    ['id' => Types::INTEGER, 'data' => Types::ARRAY]
                ];
                $this->logQuery($logger, $updateQuery[0], $updateQuery[1], $updateQuery[2]);
                if (!$dryRun) {
                    $this->connection->executeStatement($updateQuery[0], $updateQuery[1], $updateQuery[2]);
                }
            }
        }
    }
}
