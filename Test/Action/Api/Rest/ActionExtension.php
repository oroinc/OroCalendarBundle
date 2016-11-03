<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest;

use Oro\Component\Action\Model\ContextAccessor as ActionContextAccessor;

use Oro\Bundle\CalendarBundle\Test\Action\Extension\AbstractActionExtension;

/**
 * @internal
 *
 * This extension is used to inject REST API actions during tests.
*
 * It supports next actions:
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\PostAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\PostEntityAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\GetAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\PutAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\DeleteAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\RequestAction::ALIAS
 */
class ActionExtension extends AbstractActionExtension
{
    /**
     * @var ActionContextAccessor
     */
    protected $actionContextAccessor;

    /**
     * @param ActionContextAccessor $actionContextAccessor
     */
    public function __construct(ActionContextAccessor $actionContextAccessor)
    {
        $this->actionContextAccessor = $actionContextAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadActions()
    {
        return [
            GetAction::ALIAS => new GetAction($this->actionContextAccessor),
            PostAction::ALIAS => new PostAction($this->actionContextAccessor),
            PostEntityAction::ALIAS => new PostEntityAction($this->actionContextAccessor),
            PutAction::ALIAS => new PutAction($this->actionContextAccessor),
            DeleteAction::ALIAS => new DeleteAction($this->actionContextAccessor),
            RequestAction::ALIAS => new RequestAction($this->actionContextAccessor),
        ];
    }
}
