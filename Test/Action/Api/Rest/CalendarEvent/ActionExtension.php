<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent;

use Oro\Component\Action\Model\ContextAccessor as ActionContextAccessor;

use Oro\Bundle\CalendarBundle\Test\Action\Extension\AbstractActionExtension;

/**
 * @internal
 *
 * {@inheritdoc}
 *
 * This extension is used to inject Calendar Event REST API actions during tests.
*
 * It supports next actions:
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent\PostAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent\GetAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent\GetListAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent\PutAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarEvent\DeleteAction::ALIAS
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
            GetListAction::ALIAS => new GetListAction($this->actionContextAccessor),
            PostAction::ALIAS => new PostAction($this->actionContextAccessor),
            PutAction::ALIAS => new PutAction($this->actionContextAccessor),
            DeleteAction::ALIAS => new DeleteAction($this->actionContextAccessor)
        ];
    }
}
