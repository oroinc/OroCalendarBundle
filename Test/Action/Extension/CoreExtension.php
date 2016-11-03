<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Extension;

use Oro\Component\Action\Action\TreeExecutor;

/**
 * @internal
 *
 * This extension is used to inject basic actions during tests.
*
 * It supports next actions:
 *  - @see \Oro\Component\Action\Action\TreeExecutor::ALIAS
 */
class CoreExtension extends AbstractActionExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadActions()
    {
        return [
            TreeExecutor::ALIAS => new TreeExecutor(),
        ];
    }
}
