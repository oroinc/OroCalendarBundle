<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;

/**
 * Provide title for Calendar Event entity.
 */
class CalendarEventEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof CalendarEvent) {
            return false;
        }

        return $entity->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, CalendarEvent::class, true)) {
            return false;
        }

        return sprintf('%s.title', $alias);
    }
}
