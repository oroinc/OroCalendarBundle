<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class CalendarEventGridHelper
{
    /** @var Router */
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $gridName
     * @param string $keyName
     * @param array  $node
     *
     * @return callable
     */
    public function getDeleteLinkProperty($gridName, $keyName, $node)
    {
        if (!isset($node['route'])) {
            return false;
        }

        $router = $this->router;
        $route  = $node['route'];

        return function (ResultRecord $record) use ($gridName, $router, $route) {
            return $router->generate(
                $route,
                [
                    'id'              => $record->getValue('id'),
                    'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
                ]
            );
        };
    }
}
