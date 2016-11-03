<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Extension;

use Oro\Component\Action\Action\ActionInterface;

/**
 * @internal
 *
 * This is abstract extension used to inject actions during functional tests.
 */
abstract class AbstractActionExtension implements ActionExtensionInterface
{
    /**
     * @var array
     */
    protected $actions;

    /**
     * {@inheritdoc}
     */
    public function getAction($type)
    {
        $result = $this->getActionOrNull($type);

        if (!$result) {
            throw new \InvalidArgumentException(sprintf('Action with type "%s" is not supported.', $type));
        }

        return $result;
    }

    /**
     * Returns action by given $type or null if it's not supported.
     *
     * @param string $type
     * @return ActionInterface|null
     */
    protected function getActionOrNull($type)
    {
        /**
         * @todo Avoid creation of each action every time.
         */
        $this->actions = $this->loadActions();

        return isset($this->actions[$type]) ? $this->actions[$type] : null;
    }

    /**
     * Return mapping of action instances by action type.
     *
     * @return array
     */
    abstract protected function loadActions();

    /**
     * {@inheritdoc}
     */
    public function supports($type)
    {
        $action = $this->getActionOrNull($type);
        return $action !== null;
    }
}
