<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\LocaleBundle\Provider\BasePreferredLanguageProvider;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;

/**
 * Determines language for Attendee entity based on related user.
 */
class AttendeePreferredLanguageProvider extends BasePreferredLanguageProvider
{
    /**
     * @var PreferredLanguageProviderInterface
     */
    private $chainLanguageProvider;

    /**
     * @param PreferredLanguageProviderInterface $chainLanguageProvider
     */
    public function __construct(PreferredLanguageProviderInterface $chainLanguageProvider)
    {
        $this->chainLanguageProvider = $chainLanguageProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof Attendee;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPreferredLanguageForEntity($entity): string
    {
        return $this->chainLanguageProvider->getPreferredLanguage($entity->getUser());
    }
}
