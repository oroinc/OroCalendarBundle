<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

class UserCalendarProvider extends AbstractRecurrenceAwareCalendarProvider
{
    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /**
     * UserCalendarProvider constructor.
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        Model\Recurrence $recurrence,
        EntityNameResolver $entityNameResolver,
        AbstractCalendarEventNormalizer $calendarEventNormalizer
    ) {
        parent::__construct($doctrineHelper, $recurrence);
        $this->entityNameResolver      = $entityNameResolver;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
        if (empty($calendarIds)) {
            return [];
        }

        $qb = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar')
            ->createQueryBuilder('o')
            ->select('o, owner')
            ->innerJoin('o.owner', 'owner');
        $qb->where($qb->expr()->in('o.id', ':calendarIds'))->setParameter('calendarIds', $calendarIds);

        $result = [];

        /** @var Entity\Calendar[] $calendars */
        $calendars = $qb->getQuery()->getResult();
        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName' => $this->buildCalendarName($calendar),
                'userId'       => $calendar->getOwner()->getId()
            ];
            // prohibit to remove the current calendar from the list of connected calendars
            if ($calendar->getId() === $calendarId) {
                $resultItem['removable'] = false;
                $resultItem['canAddEvent']    = true;
                $resultItem['canEditEvent']   = true;
                $resultItem['canDeleteEvent'] = true;
            }
            $result[$calendar->getId()] = $resultItem;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarEvents(
        $organizationId,
        $userId,
        $calendarId,
        $start,
        $end,
        $connections,
        $extraFields = []
    ) {
        /** @var CalendarEventRepository $repo */
        $repo        = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $extraFields = $this->filterSupportedFields($extraFields, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $qb          = $repo->getUserEventListByTimeIntervalQueryBuilder($start, $end, [], $extraFields);

        $visibleIds = [];
        foreach ($connections as $id => $visible) {
            if ($visible) {
                $visibleIds[] = $id;
            }
        }
        if ($visibleIds) {
            $qb
                ->andWhere('c.id IN (:visibleIds)')
                ->setParameter('visibleIds', $visibleIds);
        } else {
            $qb
                ->andWhere('1 = 0');
        }

        // @TODO: Fix ACL for calendars providers in BAP-12973.
        $items = $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());
        $items = $this->getExpandedRecurrences($items, $start, $end);

        return $items;
    }

    /**
     * @param Entity\Calendar $calendar
     *
     * @return string
     */
    protected function buildCalendarName(Entity\Calendar $calendar)
    {
        return $calendar->getName() ?: $this->entityNameResolver->getName($calendar->getOwner());
    }
}
