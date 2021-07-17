<?php

namespace Oro\Bundle\CalendarBundle\EventListener\Datagrid;

use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarGridListener
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        SystemCalendarConfig $calendarConfig
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->calendarConfig = $calendarConfig;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        // show 'public' column only if both public and system calendars are enabled
        if (!$this->calendarConfig->isPublicCalendarEnabled() || !$this->calendarConfig->isSystemCalendarEnabled()) {
            $config = $event->getConfig();
            $this->removeColumn($config, 'public');
        }
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid   = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $isPublicGranted = $this->calendarConfig->isPublicCalendarEnabled()
                && $this->authorizationChecker->isGranted('oro_public_calendar_management');
            $isSystemGranted = $this->calendarConfig->isSystemCalendarEnabled()
                && $this->authorizationChecker->isGranted('oro_system_calendar_management');
            if ($isPublicGranted && $isSystemGranted) {
                $datasource->getQueryBuilder()
                    ->andWhere('(sc.public = :public OR sc.organization = :organizationId)')
                    ->setParameter('public', true)
                    ->setParameter('organizationId', $this->tokenAccessor->getOrganizationId());
            } elseif ($isPublicGranted) {
                $datasource->getQueryBuilder()
                    ->andWhere('sc.public = :public')
                    ->setParameter('public', true);
            } elseif ($isSystemGranted) {
                $datasource->getQueryBuilder()
                    ->andWhere('sc.organization = :organizationId')
                    ->setParameter('organizationId', $this->tokenAccessor->getOrganizationId());
            } else {
                // it is denied to view both public and system calendars
                $datasource->getQueryBuilder()
                    ->andWhere('1 = 0');
            }
        }
    }

    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            $acl = $record->getValue('public') ? 'oro_public_calendar_management' : 'oro_system_calendar_management';
            if ($this->authorizationChecker->isGranted($acl)) {
                return [];
            }
            return [
                'update' => false,
                'delete' => false,
            ];
        };
    }

    /**
     * @param DatagridConfiguration $config
     * @param string                $fieldName
     */
    protected function removeColumn(DatagridConfiguration $config, $fieldName)
    {
        $config->offsetUnsetByPath(sprintf('[columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[filters][columns][%s]', $fieldName));
        $config->offsetUnsetByPath(sprintf('[sorters][columns][%s]', $fieldName));
    }
}
