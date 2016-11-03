<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest;

/**
 * @internal
 *
 * {@inheritdoc}
 */
class DeleteAction extends RequestAction
{
    const ALIAS = 'test_api_rest_delete';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        $this->requestMethod = 'DELETE';

        return $this;
    }
}
