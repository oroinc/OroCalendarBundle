<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest;

/**
 * @internal
 *
 * {@inheritdoc}
 */
class PutAction extends RequestAction
{
    const ALIAS = 'test_api_rest_put';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        $this->requestMethod = 'PUT';

        return $this;
    }
}
