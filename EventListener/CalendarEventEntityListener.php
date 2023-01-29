<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Exception\UidAlreadySetException;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Listens to CalendarEvent
 * Generates missed UID for current and parent CalendarEvent
 * Updates recurring and parent calendar events UID
 * Throws exception in case when already created UID has been changed
 */
class CalendarEventEntityListener
{
    /** @ORM\PrePersist() */
    public function prePersist(CalendarEvent $calendarEvent, LifecycleEventArgs $event)
    {
        if ($calendarEvent->getParent() !== null && $calendarEvent->getParent()->getUid() !== null) {
            $calendarEvent->setUid($calendarEvent->getParent()->getUid());
        }

        // if calendar event is an recurring event exception and its base recurring event has an UID,
        // set it to the exception event
        if ($calendarEvent->getRecurringEvent() !== null
            && $calendarEvent->getRecurringEvent()->getUid() !== null
        ) {
            $calendarEvent->setUid($calendarEvent->getRecurringEvent()->getUid());
        }

        if ($calendarEvent->getUid() === null) {
            $calendarEvent->setUid(UUIDGenerator::v4());
        }

        $this->updateRecurrentUid($calendarEvent);
        $this->updateParentUid($calendarEvent);
    }

    /** @ORM\PreUpdate() */
    public function preUpdate(CalendarEvent $calendarEvent, PreUpdateEventArgs $event)
    {
        if (!$event->hasChangedField('uid')) {
            return;
        }

        if ($event->getOldValue('uid') !== null && ($event->getNewValue('uid') !== $event->getOldValue('uid'))) {
            throw new UidAlreadySetException(sprintf(
                'Unable to change uid for calendar event. Old: `%s`, new: `%s`',
                $event->getNewValue('uid'),
                $event->getOldValue('uid')
            ));
        }

        $this->updateRecurrentUid($calendarEvent, $event);
        $this->updateParentUid($calendarEvent, $event);
    }

    private function updateParentUid(CalendarEvent $calendarEvent, LifecycleEventArgs $event = null)
    {
        if ($calendarEvent->getParent() !== null && $calendarEvent->getParent()->getUid() === null) {
            if ($event) {
                $this->scheduleExtraUpdate($calendarEvent->getParent(), $calendarEvent->getUid(), $event);
            }

            $calendarEvent->getParent()->setUid($calendarEvent->getUid());

            // if calendar event has a parent, assign it to $calendarEvent variable to properly iterate over children
            $calendarEvent = $calendarEvent->getParent();
        }

        foreach ($calendarEvent->getChildEvents() as $childEvent) {
            if ($event) {
                $this->scheduleExtraUpdate($childEvent, $calendarEvent->getUid(), $event);
            }
            $childEvent->setUid($calendarEvent->getUid());
        }
    }

    private function updateRecurrentUid(CalendarEvent $calendarEvent, LifecycleEventArgs $event = null)
    {
        // if calendar event is an recurring event exception and its base recurring event does not have UID,
        // set the exception event's UID to the main recurring one
        if ($calendarEvent->getRecurringEvent() !== null && $calendarEvent->getRecurringEvent()->getUid() === null) {
            if ($event) {
                $this->scheduleExtraUpdate($calendarEvent->getRecurringEvent(), $calendarEvent->getUid(), $event);
            }
            $calendarEvent->getRecurringEvent()->setUid($calendarEvent->getUid());
        }
    }

    /**
     * Schedule extra update is needed, because of we are using preUpdate event which is triggered after UoW
     *  calculate all change sets
     */
    private function scheduleExtraUpdate(
        CalendarEvent $calendarEvent,
        string $newUid,
        LifecycleEventArgs $doctrineEvent
    ) {
        $doctrineEvent->getEntityManager()->getUnitOfWork()->scheduleExtraUpdate(
            $calendarEvent,
            ['uid' => [$calendarEvent->getUid(), $newUid]]
        );
    }
}
