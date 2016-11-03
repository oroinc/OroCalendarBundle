<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest;

/**
 * @internal
 *
 * {@inheritdoc}
 */
class PostAction extends RequestAction
{
    const ALIAS = 'test_api_rest_post';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        parent::initialize($options);

        $this->requestMethod = 'POST';

        return $this;
    }
}
