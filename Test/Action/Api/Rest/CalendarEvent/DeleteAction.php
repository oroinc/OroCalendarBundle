<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent;

use Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\DeleteAction as BaseDeleteAction;

/**
 * @internal
 *
 * {@inheritdoc}
 */
class DeleteAction extends BaseDeleteAction
{
    const ALIAS = 'test_api_rest_delete_calendar_event';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);
        $this->requestRoute = 'oro_api_delete_calendarevent';

        return $this;
    }
}
