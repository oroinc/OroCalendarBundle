<?php

namespace Oro\Bundle\CalendarBundle\Datagrid\MassAction;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler as ParentHandler;

/**
 * Delete mass action handler for CalendarEvent entity.
 */
class DeleteMassActionHandler extends ParentHandler
{
    private DeleteManager $deleteManager;

    public function setDeleteManager(DeleteManager $deleteManager): void
    {
        $this->deleteManager = $deleteManager;
    }

    #[\Override]
    protected function processDelete(object $entity, EntityManagerInterface $manager): void
    {
        /** @var CalendarEvent $entity */
        $this->deleteManager->deleteOrCancel($entity, true);
    }
}
