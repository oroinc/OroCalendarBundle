<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Listener that add organization information for the calendar event entity to the search index
 * and sets the record url if the calendar event belogs to the system calendar during search.
 */
class CalendarEventSearchListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(UrlGeneratorInterface $urlGenerator, ManagerRegistry $doctrine)
    {
        $this->urlGenerator = $urlGenerator;
        $this->doctrine = $doctrine;
    }

    /**
     * Sets the organization data of the calendar event entity to the search item data.
     */
    public function prepareEntityMapEvent(PrepareEntityMapEvent $event)
    {
        $data = $event->getData();
        $className = $event->getClassName();
        if ($className !== CalendarEvent::class) {
            return;
        }

        /** @var $entity CalendarEvent */
        $entity = $event->getEntity();

        $organizationId = 0;
        if (null !== $entity->getCalendar()) {
            $organizationId = $entity->getCalendar()->getOrganization()->getId();
        } elseif (null !== $entity->getSystemCalendar() && false === $entity->getSystemCalendar()->isPublic()) {
            $organizationId = $entity->getSystemCalendar()->getOrganization()->getId();
        }

        if (!isset($data['integer'])) {
            $data['integer'] = [];
        }

        $data['integer']['organization'] = $organizationId;

        $event->setData($data);
    }

    /**
     * Sets the record url if the calendar event belogs to the system calendar.
     */
    public function prepareResultItemEvent(PrepareResultItemEvent $event)
    {
        $resultItem = $event->getResultItem();
        if ($resultItem->getEntityName() !== CalendarEvent::class) {
            return;
        }

        /** @var CalendarEvent $entity */
        $entity = $event->getEntity();
        if (null === $entity) {
            $entity = $this->doctrine->getManagerForClass(CalendarEvent::class)
                ->getRepository(CalendarEvent::class)
                ->find($event->getResultItem()->getRecordId());
        }

        if (null === $entity->getSystemCalendar()) {
            return;
        }

        $event->getResultItem()->setRecordUrl(
            $this->urlGenerator->generate(
                'oro_system_calendar_event_view',
                ['id' =>  $resultItem->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }
}
