<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides a method to build URL to be used to delete calendar event.
 */
class CalendarEventGridHelper
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function getDeleteLinkProperty(string $gridName, string $keyName, array $node): callable
    {
        if (!isset($node['route'])) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot build callable fo grid "%s" because the "route" option is mandatory.',
                $gridName
            ));
        }

        $route = $node['route'];

        return function (ResultRecord $record) use ($route) {
            return $this->urlGenerator->generate(
                $route,
                [
                    'id'              => $record->getValue('id'),
                    'notifyAttendees' => NotificationManager::ALL_NOTIFICATIONS_STRATEGY,
                ]
            );
        };
    }
}
