<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with attendees:
 *   - is_attendees_invitation_enabled
 */
class AttendeesExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('is_attendees_invitation_enabled', [$this, 'isAttendeesInvitationEnabled'])
        ];
    }

    public function isAttendeesInvitationEnabled(): bool
    {
        return $this->getFeatureChecker()->isFeatureEnabled('calendar_events_attendee_notifications');
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            FeatureChecker::class
        ];
    }

    private function getFeatureChecker(): FeatureChecker
    {
        return $this->container->get(FeatureChecker::class);
    }
}
