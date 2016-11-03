<?php

namespace Oro\Bundle\CalendarBundle\Test;

use Oro\Bundle\CalendarBundle\Test\Action\ActionAssembler;
use Oro\Bundle\CalendarBundle\Test\Context\ContextInterface;
use Oro\Component\Action\Action\ActionInterface;

/**
 * @internal
 */
class TestCaseStep
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var integer
     */
    protected $index;

    /**
     * @var array
     */
    protected $actionConfiguration;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return TestCaseStep
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return TestCaseStep
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     * @return TestCaseStep
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @return array
     */
    public function getActionConfiguration()
    {
        return $this->actionConfiguration;
    }

    /**
     * @param array $actionConfiguration
     * @return TestCaseStep
     */
    public function setActionConfiguration($actionConfiguration)
    {
        $this->actionConfiguration = $actionConfiguration;
        return $this;
    }

    /**
     * @param ActionAssembler $actionAssembler
     * @param ContextInterface $context
     */
    public function execute(ActionAssembler $actionAssembler, ContextInterface $context)
    {
        $context->refreshReferences();
        $action = $this->assembleAction($actionAssembler);
        $this->executeAction($action, $context);
    }

    /**
     * @param ActionAssembler $actionAssembler
     * @return ActionInterface
     * @throws \PHPUnit_Framework_Exception
     */
    protected function assembleAction(ActionAssembler $actionAssembler)
    {
        try {
            $action = $actionAssembler->assemble($this->getActionConfiguration());
        } catch (\Exception $exception) {
            $message = sprintf(
                "Step assembling failed.\nStep #%d. %s",
                $this->getIndex(),
                $this->getTitle()
            );
            throw new \PHPUnit_Framework_Exception($message, null, $exception);
        }

        return $action;
    }

    /**
     * @param ActionInterface $action
     * @param ContextInterface $context
     * @throws \PHPUnit_Framework_Exception
     */
    protected function executeAction(ActionInterface $action, ContextInterface $context)
    {
        try {
            $action->execute($context);
        } catch (\Exception $exception) {
            $message = sprintf(
                "Step execution failed.\nStep #%d. %s",
                $this->getIndex(),
                $this->getTitle()
            );
            throw new \PHPUnit_Framework_Exception($message, null, $exception);
        }
    }

    /**
     * @param array $config
     * @return TestCaseStep
     */
    public static function createFromConfig(array $config)
    {
        $result = new static();

        if (isset($config['index'])) {
            $result->setIndex($config['index']);
        }

        if (isset($config['title'])) {
            $result->setTitle($config['title']);
        }

        if (isset($config['description'])) {
            $result->setDescription($config['description']);
        }

        if (isset($config['actions'])) {
            $result->setActionConfiguration($config['actions']);
        }

        return $result;
    }
}
