<?php

namespace Oro\Bundle\CalendarBundle\Util;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Exception\UidAlreadySetException;

class CalendarEventUidSpreader
{
    /**
     * @param CalendarEvent $event
     * @throws \Oro\Bundle\CalendarBundle\Exception\UidAlreadySetException
     */
    public function process(CalendarEvent $event)
    {
        $alreadySetChildEvent = null;

        if ($event->getParent() !== null) {
            $this->setOrThrowException($event->getParent(), $event->getUid());
            $alreadySetChildEvent = $event;
            $event = $event->getParent();
        }

        foreach ($event->getChildEvents() as $childEvent) {
            if ($alreadySetChildEvent === null || $childEvent !== $alreadySetChildEvent) {
                $this->setOrThrowException($childEvent, $event->getUid());
            }
        }
    }

    /**
     * @param CalendarEvent $event
     * @param string $uid
     * @throws \Oro\Bundle\CalendarBundle\Exception\UidAlreadySetException
     */
    private function setOrThrowException(CalendarEvent $event, $uid)
    {
        if ($event->getUid() !== null) {
            throw new UidAlreadySetException(
                sprintf(
                    'Unable to set uid "%s" to event %s, because this event already has uid: "%s"',
                    $uid,
                    $event->getId(),
                    $event->getUid()
                )
            );
        }

        $event->setUid($uid);
    }
}
