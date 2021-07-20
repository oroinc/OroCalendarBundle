<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Model;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Represents system wide calendars
 */
class PublicCalendarProvider extends AbstractRecurrenceAwareCalendarProvider
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
        if (!$this->calendarConfig->isPublicCalendarEnabled()) {
            return array_fill_keys($calendarIds, null);
        }

        $result = [];
        /** @var SystemCalendarRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $qb   = $repo->getPublicCalendarsQueryBuilder();
        /** @var SystemCalendar[] $calendars */
        $calendars = $qb->getQuery()->getResult();

        $isEventManagementGranted = $this->authorizationChecker->isGranted('oro_public_calendar_management');
        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName'    => $calendar->getName(),
                'backgroundColor' => $calendar->getBackgroundColor(),
                'removable'       => false,
                'position'        => -80,
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
        if (!$this->calendarConfig->isPublicCalendarEnabled()) {
            return [];
        }

        /** @var CalendarEventRepository $repo */
        $repo         = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $extraFields  = $this->filterSupportedFields($extraFields, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $qb           = $repo->getPublicEventListByTimeIntervalQueryBuilder(
            $start,
            $end,
            [],
            $extraFields
        );
        $invisibleIds = [];
        foreach ($connections as $id => $visible) {
            if (!$visible) {
                $invisibleIds[] = $id;
            }
        }
        if (!empty($invisibleIds)) {
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
