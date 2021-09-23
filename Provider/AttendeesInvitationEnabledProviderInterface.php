<?php

namespace Oro\Bundle\CalendarBundle\Provider;

/**
 * Interface of provider that returns whether the attendees invitations are enabled.
 *
 * @deprecated Feature checker will be used instead,
 * (@see ../Resources/config/oro/features.yml)
 */
interface AttendeesInvitationEnabledProviderInterface
{
    /**
     * Returns true if invitations are enabled, otherwise returns false.
     */
    public function isAttendeesInvitationEnabled(): bool;
}
