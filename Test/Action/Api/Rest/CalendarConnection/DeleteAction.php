<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarConnection;

use Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\DeleteAction as BaseDeleteAction;

/**
 * @internal
 */
class DeleteAction extends BaseDeleteAction
{
    const ALIAS = 'test_api_rest_delete_calendar_connection';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);
        $this->requestRoute = 'oro_api_delete_calendar_connection';

        return $this;
    }
}
