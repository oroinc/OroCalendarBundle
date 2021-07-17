<?php

namespace Oro\Bundle\CalendarBundle\Manager\CalendarEvent;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class MatchingEventsManager
{
    /**
     * @var CalendarEventRepository
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onEventUpdate(CalendarEvent $event)
    {
        if (!$this->eventIsNew($event) || $event->isOrganizer() !== true) {
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

    private function eventIsNew(CalendarEvent $event): bool
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

    private function mergeAttendeeToExistingCalendarEvent(Attendee $attendee, CalendarEvent $matchingEvent)
    {
        $event = $attendee->getCalendarEvent();

        $matchingEvent->setRelatedAttendee($attendee);
        $attendeesToRemove = $matchingEvent->getAttendees();
        $matchingEvent->setAttendees(new ArrayCollection());

        if ($event->getOrganizerUser() !== null) {
            $matchingEvent->setOrganizerUser($event->getOrganizerUser());
        }
        // Attendee entity is an owning side of relation, so we need to manually remove it
        if (count($attendeesToRemove) > 0) {
            foreach ($attendeesToRemove as $attendeeToRemove) {
                $this->doctrineHelper->getEntityManagerForClass(CalendarEvent::class)->remove($attendeeToRemove);
            }
        }
        $event->addChildEvent($matchingEvent);
    }
}
