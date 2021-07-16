<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Represents organization calendars
 */
class SystemCalendarProvider extends AbstractRecurrenceAwareCalendarProvider
{
    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        Model\Recurrence $recurrence,
        AbstractCalendarEventNormalizer $calendarEventNormalizer,
        SystemCalendarConfig $calendarConfig,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($doctrineHelper, $recurrence);
        $this->calendarEventNormalizer = $calendarEventNormalizer;
        $this->calendarConfig = $calendarConfig;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
        if (!$this->calendarConfig->isSystemCalendarEnabled()) {
            return array_fill_keys($calendarIds, null);
        }

        $result = [];
        /** @var SystemCalendarRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');

        //@TODO: temporary return all system calendars until BAP-6566 implemented
        //$qb = $repo->getSystemCalendarsByIdsQueryBuilder($calendarIds);
        $qb = $repo->getSystemCalendarsQueryBuilder($organizationId);

        /** @var SystemCalendar[] $calendars */
        $calendars = $qb->getQuery()->getResult();

        $isEventManagementGranted = $this->authorizationChecker->isGranted('oro_system_calendar_management');
        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName'    => $calendar->getName(),
                'backgroundColor' => $calendar->getBackgroundColor(),
                'removable'       => false,
                'position'        => -60,
            ];
            if ($isEventManagementGranted) {
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
        if (!$this->calendarConfig->isSystemCalendarEnabled()
            || !$this->authorizationChecker->isGranted('oro_system_calendar_management')
        ) {
            return [];
        }

        //@TODO: temporary return all system calendars until BAP-6566 implemented
        ///** @var CalendarEventRepository $repo */
        //$repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        //$qb = $repo->getSystemEventListByTimeIntervalQueryBuilder(
        //    $calendarId,
        //    $start,
        //    $end,
        //    []
        //);
        $extraFields = $this->filterSupportedFields($extraFields, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        /** @var CalendarEventRepository $repo */
        $repo         = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $qb           = $repo->getSystemEventListByTimeIntervalQueryBuilder(
            $start,
            $end,
            [],
            $extraFields
        )
            ->andWhere('c.organization = :organizationId')
            ->setParameter('organizationId', $organizationId);
        $invisibleIds = [];
        foreach ($connections as $id => $visible) {
            if (!$visible) {
                $invisibleIds[] = $id;
            }
        }
        if ($invisibleIds) {
            $qb
                ->andWhere('c.id NOT IN (:invisibleIds)')
                ->setParameter('invisibleIds', $invisibleIds);
        }

        // @TODO: Fix ACL for calendars providers in BAP-12973.
        $items = $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());

        $items = $this->getExpandedRecurrences($items, $start, $end);

        return $items;
    }
}
