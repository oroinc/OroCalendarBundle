<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarConnection;

use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\PostEntityAction;

/**
 * @internal
 *
 * {@inheritdoc}
 */
class PostAction extends PostEntityAction
{
    const ALIAS = 'test_api_rest_post_calendar_connection';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        $this->requestRoute = 'oro_api_post_calendar_connection';
        $this->entityClass = CalendarProperty::class;

        return $this;
    }
}
