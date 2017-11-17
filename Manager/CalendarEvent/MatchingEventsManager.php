<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class MatchingEventsManager
{
    /**
     * @var CalendarEventRepository
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param CalendarEvent $event
     */
    public function onEventUpdate(CalendarEvent $event)
    {
        if (!$this->eventIsNew($event)) {
            return;
        }

        $matchingEvents = $this->getRepository()->findEventsWithMatchingUidAndOrganizer($event);

        foreach ($matchingEvents as $matchingEvent) {
            $calendar = $matchingEvent->getCalendar();

            if ($calendar === null) {
                continue;
            }

            $attendee = $event->getAttendeeByEmail($calendar->getOwner()->getEmail());

            if ($attendee === null) {
                continue;
            }

            $this->mergeAttendeeToExistingCalendarEvent($attendee, $matchingEvent);
        }
    }

    /**
     * @param CalendarEvent $event
     * @return bool
     */
    private function eventIsNew(CalendarEvent $event)
    {
        return $event->getId() === null;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|CalendarEventRepository
     */
    private function getRepository()
    {
        return $this->doctrineHelper->getEntityManagerForClass(CalendarEvent::class)
            ->getRepository(CalendarEvent::class);
    }

    /**
     * @param Attendee $attendee
     * @param CalendarEvent $matchingEvent
     */
    private function mergeAttendeeToExistingCalendarEvent(Attendee $attendee, CalendarEvent $matchingEvent)
    {
        $event = $attendee->getCalendarEvent();

        $matchingEvent->setRelatedAttendee($attendee);
        $event->addChildEvent($matchingEvent);
    }
}
