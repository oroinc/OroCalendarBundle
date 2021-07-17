<?php

namespace Oro\Bundle\CalendarBundle\EventListener\Datagrid;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

/**
 * Automatically applies a filter for the events that are:
 * - related to the current entity
 * - start or end after the current date
 */
class ActivityGridListener
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var LocaleSettings */
    protected $localeSettings;

    public function __construct(
        ActivityManager $activityManager,
        EntityRoutingHelper $entityRoutingHelper,
        LocaleSettings $localeSettings
    ) {
        $this->activityManager     = $activityManager;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->localeSettings      = $localeSettings;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters = $datagrid->getParameters();
            $entityClass = $this->entityRoutingHelper->resolveEntityClass($parameters->get('entityClass'));
            $entityId = $parameters->get('entityId');

            $qb = $datasource->getQueryBuilder();

            // apply activity filter
            $this->activityManager->addFilterByTargetEntity($qb, $entityClass, $entityId);

            // apply filter by date
            $start = new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));
            $start->setTime(0, 0, 0);
            $qb->andWhere('event.start >= :date OR event.end >= :date')
                ->setParameter('date', $start, Types::DATETIME_MUTABLE);
        }
    }
}
