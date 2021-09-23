<?php

namespace Oro\Bundle\CalendarBundle\Provider;

/**
 * Provider that returns whether the attendees invitations are enabled.
 *
 * @deprecated Feature checker will be used instead,
 * (@see ../Resources/config/oro/features.yml)
 */
class AttendeesInvitationEnabledProvider
{
    /** @var iterable|AttendeesInvitationEnabledProviderInterface[] */
    private iterable $providers;

    /**
     * @param iterable|AttendeesInvitationEnabledProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function isAttendeesInvitationEnabled(): bool
    {
        foreach ($this->providers as $provider) {
            if (false === $provider->isAttendeesInvitationEnabled()) {
                return false;
            }
        }

        return true;
    }
}
