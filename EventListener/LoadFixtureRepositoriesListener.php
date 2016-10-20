<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;

class LoadFixtureRepositoriesListener
{
    /**
     * Defines fixture repositories to be loaded
     *
     * @param GenericEvent $event
     */
    public function onLoadFixtureRepositories(GenericEvent $event)
    {
        $criteria = new Criteria((new ExpressionBuilder())->gt('id', 1));

        $extendedArgs = [
            'OroCalendarBundle:Calendar'         => $criteria,
            'OroCalendarBundle:CalendarEvent'    => null,
            'OroCalendarBundle:CalendarProperty' => $criteria,
        ];

        if ($event->hasArgument('OroMigrationBundle:DataFixture')) {
            /** @var Criteria $migrationsCriteria */
            $migrationsCriteria = $event->getArgument('OroMigrationBundle:DataFixture');

            $migrationsCriteria
                // delete CalendarBundle related data
                ->orWhere((new ExpressionBuilder())->contains(
                    'className',
                    'Oro\\\\Bundle\\\\CalendarBundle\\\\Migrations\\\\Data'
                ))
                // delete CalendarCRM bridge related data
                ->orWhere((new ExpressionBuilder())->contains(
                    'className',
                    'Oro\\\\Bridge\\\\CalendarCRM\\\\Migrations\\\\Data'
                ));

            $extendedArgs['OroMigrationBundle:DataFixture'] = $migrationsCriteria;
        }

        $args = array_merge($event->getArguments(), $extendedArgs);
        $event->setArguments($args);
    }
}
