<?php

namespace Oro\Bundle\CalendarBundle\Datagrid\MassAction;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler as ParentHandler;

/**
 * Class DeleteMassActionHandler
 *
 * @package Oro\Bundle\CalendarBundle\Datagrid\MassAction
 */
class DeleteMassActionHandler extends ParentHandler
{
    /**
     * @inheritdoc
     */
    protected function processDelete($entity, EntityManager $manager)
    {
        if ($entity->getRecurringEvent()) {
            $event = $entity->getParent() ? : $entity;
            $event->setCancelled(true);

            $childEvents = $event->getChildEvents();
            foreach ($childEvents as $childEvent) {
                $childEvent->setCancelled(true);
            }
        } else {
            if ($entity->getRecurrence() && $entity->getRecurrence()->getId()) {
                $manager->remove($entity->getRecurrence());
            }

            if ($entity->getRecurringEvent()) {
                $event = $entity->getParent() ? : $entity;
                $childEvents = $event->getChildEvents();
                foreach ($childEvents as $childEvent) {
                    $manager->remove($childEvent);
                }
            }
            $manager->remove($entity);
        }

        return $this;
    }
}
