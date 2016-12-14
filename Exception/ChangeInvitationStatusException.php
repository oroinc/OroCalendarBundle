<?php

namespace Oro\Bundle\CalendarBundle\Exception;

/**
 * The exception class is used when error of changing invitation status of the event occurs.
 */
class ChangeInvitationStatusException extends \Exception implements ExceptionInterface
{
    /**
     * Creates exception for case when user cannot change invitation status of the event.
     *
     * @return self
     */
    public static function changeInvitationStatusFailedWhenRelatedAttendeeNotExist()
    {
        return new self('Cannot change invitation status of the event with no related attendee.');
    }

    /**
     * Creates exception for case when user cannot change invitation status of the event.
     *
     * @return self
     */
    public static function changeInvitationFailed()
    {
        return new self('Cannot change invitation status of the event.');
    }

    /**
     * Creates exception for case when invitation status is not found.
     *
     * @param string $statusName
     * @return self
     */
    public static function invitationStatusNotFound($statusName)
    {
        return new self(
            sprintf(
                'Status "%s" does not exists.',
                $statusName
            )
        );
    }
}
