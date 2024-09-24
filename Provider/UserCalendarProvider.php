<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

/**
 * The implementation of the calendar provider for the user calendar.
 */
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

    #[\Override]
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
        if (empty($calendarIds)) {
            return [];
        }

        $qb = $this->doctrineHelper->getEntityRepository(Calendar::class)
            ->createQueryBuilder('o')
            ->select('o, owner')
            ->innerJoin('o.owner', 'owner');
        $qb->where($qb->expr()->in('o.id', ':calendarIds'))->setParameter('calendarIds', $calendarIds);

        $result = [];

        /** @var Calendar[] $calendars */
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

    #[\Override]
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
        $repo        = $this->doctrineHelper->getEntityRepository(CalendarEvent::class);
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

        $items = $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());
        $items = $this->getExpandedRecurrences($items, $start, $end);

        return $items;
    }

    /**
     * @param Calendar $calendar
     *
     * @return string
     */
    protected function buildCalendarName(Calendar $calendar)
    {
        return $calendar->getName() ?: $this->entityNameResolver->getName($calendar->getOwner());
    }
}
