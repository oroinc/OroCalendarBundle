<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

abstract class HangoutsCallDependentTestCase extends AbstractTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function assertResponseEquals(array $expectedResponse, array $actualResponse, $strictCompare = true)
    {
        if (isset($actualResponse[0]) && is_array($actualResponse[0])) {
            foreach ($actualResponse as $key => $item) {
                $this->unsetUseHangoutFlag($item);
                $actualResponse[$key] = $item;
            }
        } else {
            $this->unsetUseHangoutFlag($actualResponse);
        }

        parent::assertResponseEquals($expectedResponse, $actualResponse, $strictCompare);
    }

    /**
     * @param array $data
     */
    protected function unsetUseHangoutFlag(&$data)
    {
        if (array_key_exists('use_hangout', $data)) {
            unset($data['use_hangout']);
        }
    }
}
