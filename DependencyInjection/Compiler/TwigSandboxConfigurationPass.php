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
    /**
     * {@inheritdoc}
     */
    protected function getFunctions(): array
    {
        return [
            'calendar_date_range',
            'calendar_date_range_organization',
            'get_event_recurrence_pattern'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTags(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return [
            'oro_calendar.twig.dateformat',
            'oro_calendar.twig.recurrence'
        ];
    }
}
