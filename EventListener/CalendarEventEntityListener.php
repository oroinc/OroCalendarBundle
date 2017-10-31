<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Exception\UidAlreadySetException;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

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

        // if calendar event is an recurring event exception and its base recurring event does not have UID,
        // set the exception event's UID to the main recurring one
        if ($calendarEvent->getRecurringEvent() !== null && $calendarEvent->getRecurringEvent()->getUid() === null) {
            $calendarEvent->getRecurringEvent()->setUid($calendarEvent->getUid());
        }

        $this->updateParentAfterGenerate($calendarEvent);
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
    }

    /**
     * @param CalendarEvent $calendarEvent
     */
    private function updateParentAfterGenerate(CalendarEvent $calendarEvent)
    {
        if ($calendarEvent->getParent() !== null && $calendarEvent->getParent()->getUid() === null) {
            $calendarEvent->getParent()->setUid($calendarEvent->getUid());

            // if calendar event has a parent, assign it to $calendarEvent variable to properly iterate over children
            $calendarEvent = $calendarEvent->getParent();
        }

        foreach ($calendarEvent->getChildEvents() as $childEvent) {
            $childEvent->setUid($calendarEvent->getUid());
        }
    }
}
