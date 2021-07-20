<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SystemCalendarEventGridHelper
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getPublicActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($this->authorizationChecker->isGranted('oro_public_calendar_management')) {
                return [];
            } else {
                return [
                    'update' => false,
                    'delete' => false,
                ];
            }
        };
    }

    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getSystemActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($this->authorizationChecker->isGranted('oro_system_calendar_management')) {
                return [];
            } else {
                return [
                    'update' => false,
                    'delete' => false,
                ];
            }
        };
    }
}
