<?php

namespace Oro\Bundle\CalendarBundle\Test\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;

use Oro\Bundle\CalendarBundle\Test\Action\Extension\ActionExtensionInterface;

/**
 * @internal
 *
 * This factory is used to create actions during tests.
 */
class ActionFactory implements ActionFactoryInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ActionExtensionInterface[]
     */
    protected $extensions = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ActionExtensionInterface $extension
     */
    public function addExtension(ActionExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * Creates an action supported in tests.
     *
     * @param string $type Action type
     * @param array $options
     * @param ExpressionInterface $condition
     * @return ActionInterface
     * @throws \InvalidArgumentException
     */
    public function create($type, array $options = array(), ExpressionInterface $condition = null)
    {
        $result = $this->getActionOrNull($type);

        if (!$result) {
            throw new \InvalidArgumentException(sprintf('Action with type "%s" is not supported.', $type));
        }

        $this->initializeAction($result, $options, $condition);

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
        foreach ($this->extensions as $extension) {
            if ($extension->supports($type)) {
                return $extension->getAction($type);
            }
        }

        return null;
    }

    /**
     * @param ActionInterface $action
     * @param array $options
     * @param ExpressionInterface $condition
     */
    protected function initializeAction(
        ActionInterface $action,
        array $options = array(),
        ExpressionInterface $condition = null
    ) {
        $action->initialize($options);
        if ($condition) {
            $action->setCondition($condition);
        }
        if ($action instanceof AbstractAction) {
            $action->setDispatcher($this->eventDispatcher);
        }
    }
}
