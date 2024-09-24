<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\AbstractPreferredLocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;

/**
 * Determines localization for Attendee entity based on related user.
 */
class AttendeePreferredLocalizationProvider extends AbstractPreferredLocalizationProvider
{
    /** @var PreferredLocalizationProviderInterface */
    private $innerLocalizationProvider;

    public function __construct(PreferredLocalizationProviderInterface $innerLocalizationProvider)
    {
        $this->innerLocalizationProvider = $innerLocalizationProvider;
    }

    #[\Override]
    public function supports($entity): bool
    {
        return $entity instanceof Attendee;
    }

    /**
     * @param Attendee $entity
     * @return Localization|null
     */
    #[\Override]
    protected function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->innerLocalizationProvider->getPreferredLocalization($entity->getUser());
    }
}
