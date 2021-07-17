<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\DeleteManager;
use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandler;

/**
 * The delete handler for CalendarEvent entity.
 * The following options are supported:
 * * cancelInsteadDelete - bool - whether the calendar event should be canceled instead of deleted
 * * notifyAttendees - string - a strategy that should be used to notify the calendar event attendees
 */
class CalendarEventDeleteHandler extends AbstractEntityDeleteHandler
{
    /** @var DeleteManager */
    private $deleteManager;

    public function __construct(DeleteManager $deleteManager)
    {
        $this->deleteManager = $deleteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, bool $flush = true, array $options = []): ?array
    {
        $this->assertDeleteGranted($entity);
        // clone the entity to have all attributes in notification email about calendar event is cancelled
        $clonedEntity = clone $entity;
        $this->deleteWithoutFlush($entity, $options);

        $flushOptions = $options;
        $flushOptions[self::ENTITY] = $entity;
        $flushOptions['originalEntity'] = $clonedEntity;
        if ($flush) {
            $this->flush($flushOptions);

            return null;
        }

        return $flushOptions;
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteWithoutFlush($entity, array $options): void
    {
        /** @var CalendarEvent $entity */

        $this->deleteManager->deleteOrCancel($entity, $options['cancelInsteadDelete'] ?? false);
    }
}
