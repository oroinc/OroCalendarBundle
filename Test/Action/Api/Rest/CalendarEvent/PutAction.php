<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent;

use Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\PutAction as BasePutAction;

/**
 * @internal
 *
 * {@inheritdoc}
 */
class PutAction extends BasePutAction
{
    const ALIAS = 'test_api_rest_put_calendar_event';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);
        $this->requestRoute = 'oro_api_put_calendarevent';

        return $this;
    }
}
