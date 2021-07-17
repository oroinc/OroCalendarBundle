<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Update calendar event updatedAt when attendee changed
 */
class CalendarEventAttendeesListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $newEntities = $unitOfWork->getScheduledEntityInsertions();
        $updateEntities = $unitOfWork->getScheduledEntityUpdates();
        $deletedEntities = $unitOfWork->getScheduledEntityDeletions();

        foreach ($newEntities as $entity) {
            if ($this->isAttendeeApplicable($entity, $unitOfWork)) {
                $this->updateCalendarEventUpdatedAt($entity->getCalendarEvent(), $unitOfWork);
            }
        }
        foreach ($updateEntities as $entity) {
            if ($this->isAttendeeApplicable($entity, $unitOfWork)) {
                $this->updateCalendarEventUpdatedAt($entity->getCalendarEvent(), $unitOfWork);
            }
        }
        foreach ($deletedEntities as $entity) {
            if ($this->isAttendeeApplicable($entity, $unitOfWork)
                && !$unitOfWork->isScheduledForDelete($entity->getCalendarEvent())
            ) {
                $this->updateCalendarEventUpdatedAt($entity->getCalendarEvent(), $unitOfWork);
            }
        }
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function isAttendeeApplicable($entity, UnitOfWork $unitOfWork)
    {
        return $entity instanceof Attendee
            && $entity->getCalendarEvent()
            && !$entity->getCalendarEvent()->isUpdatedAtSet()
            && count($unitOfWork->getEntityChangeSet($entity->getCalendarEvent())) == 0;
    }

    protected function updateCalendarEventUpdatedAt(CalendarEvent $calendarEvent, UnitOfWork $unitOfWork)
    {
        $oldUpdatedAt = $calendarEvent->getUpdatedAt();
        $newUpdatedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $calendarEvent->setUpdatedAt($newUpdatedAt);
        $unitOfWork->propertyChanged($calendarEvent, 'updatedAt', $oldUpdatedAt, $newUpdatedAt);
    }
}
