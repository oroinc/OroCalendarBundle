<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest;

/**
 * @internal
 *
 * {@inheritdoc}
 */
class GetAction extends RequestAction
{
    const ALIAS = 'test_api_rest_get';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        $this->requestMethod = 'GET';

        return $this;
    }
}
