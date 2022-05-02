<?php

namespace Oro\Bundle\CalendarBundle;

use Oro\Bundle\CalendarBundle\DependencyInjection\Compiler\CalendarProviderPass;
use Oro\Bundle\CalendarBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCalendarBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CalendarProviderPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
