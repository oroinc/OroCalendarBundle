<?php

namespace Oro\Bundle\CalendarBundle\Datagrid\MassAction;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler as ParentHandler;

class DeleteMassActionHandler extends ParentHandler
{
    /**
     * @var DeleteManager
     */
    protected $deleteManager;

    /**
     * @param DeleteManager $deleteManager
     */
    public function setDeleteManager($deleteManager)
    {
        $this->deleteManager = $deleteManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function processDelete($entity, EntityManager $manager)
    {
        $this->deleteManager->deleteOrCancel($entity, true);

        return $this;
    }
}
