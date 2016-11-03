<?php

namespace Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarConnection;

use Oro\Component\Action\Model\ContextAccessor as ActionContextAccessor;

use Oro\Bundle\CalendarBundle\Test\Action\Extension\AbstractActionExtension;

/**
 * @internal
 *
 * {@inheritdoc}
 *
 * This extension is used to inject Calendar Connection REST API actions during tests.
*
 * It supports next actions:
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarConnection\PostAction::ALIAS
 *  - @see \Oro\Bundle\CalendarBundle\Test\Action\Api\Rest\CalendarConnection\DeleteAction::ALIAS
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
            PostAction::ALIAS => new PostAction($this->actionContextAccessor),
            DeleteAction::ALIAS => new DeleteAction($this->actionContextAccessor)
        ];
    }
}
