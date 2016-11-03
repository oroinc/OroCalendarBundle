<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Extension;

use Oro\Component\Action\Action\ActionInterface;

/**
 * Extends actions in tests so custom actions could be injected.
 */
interface ActionExtensionInterface
{
    /**
     * Returns TRUE if extension supports given $type
     *
     * @param string $type
     * @return boolean
     */
    public function supports($type);

    /**
     * Returns an instance of action with given $type
     *
     * @param string $type
     * @return ActionInterface
     * @throws \InvalidArgumentException Thrown is action with given $type is not supported.
     */
    public function getAction($type);
}
