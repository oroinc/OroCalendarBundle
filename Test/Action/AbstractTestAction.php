<?php

namespace Oro\Bundle\CalendarBundle\Test\Action;

use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * @internal
 */
abstract class AbstractTestAction implements ActionInterface
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var ExpressionInterface
     */
    protected $condition;

    /**
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(ContextAccessor $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function setCondition(ExpressionInterface $condition)
    {
        $this->condition = $condition;
    }

    /**
     * @param ContextInterface $context
     */
    public function execute($context)
    {
        WebTestCase::assertInstanceOf(ContextInterface::class, $context);

        if ($this->isAllowed($context)) {
            $this->executeAction($context);
        }
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isAllowed($context)
    {
        if (!$this->condition) {
            return true;
        }

        return $this->condition->evaluate($context) ? true : false;
    }

    /**
     * @param mixed $context
     */
    abstract protected function executeAction($context);
}
