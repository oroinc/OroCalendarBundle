<?php

namespace Oro\Bundle\CalendarBundle\Exception;

/**
 * Exception thrown when attempting to set a UID that is already set.
 *
 * Indicates that a calendar event UID cannot be modified once it has been set.
 */
class UidAlreadySetException extends \LogicException
{
}
