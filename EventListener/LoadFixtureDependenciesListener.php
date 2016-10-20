<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;

class LoadFixtureDependenciesListener
{
    const B2C_NAMESPACE = 'Oro\Bundle\CalendarBundle\Migrations\Data\B2C\ORM';

    /**
     * Defines fixture dependencies to be loaded
     *
     * @param GenericEvent $event
     */
    public function onLoadFixtureDependencies(GenericEvent $event)
    {
        $args = array_merge($event->getArguments(), [
            static::B2C_NAMESPACE . '\\LoadUsersCalendarData',
        ]);

        $event->setArguments($args);
    }
}
