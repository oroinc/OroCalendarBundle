<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection\Compiler;

use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;

/**
 * Registers the following Twig functions for the email templates rendering sandbox:
 * * calendar_date_range
 * * calendar_date_range_organization
 * * get_event_recurrence_pattern
 */
class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    #[\Override]
    protected function getFunctions(): array
    {
        return [
            'calendar_date_range',
            'calendar_date_range_organization',
            'get_event_recurrence_pattern'
        ];
    }

    #[\Override]
    protected function getFilters(): array
    {
        return [];
    }

    #[\Override]
    protected function getTags(): array
    {
        return [];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            'oro_calendar.twig.dateformat',
            'oro_calendar.twig.recurrence'
        ];
    }
}
