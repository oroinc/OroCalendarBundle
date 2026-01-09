<?php

namespace Oro\Bundle\CalendarBundle\Exception;

/**
 * Exception thrown when attempting to add reminders to non-user calendar events.
 *
 * Indicates that only user calendar events can have reminders associated with them.
 */
class NotUserCalendarEvent extends \LogicException implements ExceptionInterface
{
    /**
     * NotUserCalendarEvent constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->message  = sprintf('Only user\'s calendar events can have reminders. Event Id: %d.', $id);

        parent::__construct($this->getMessage(), $this->getCode(), $this);
    }
}
