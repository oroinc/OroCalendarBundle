<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all calendar providers.
 */
class CalendarProviderPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = $this->findAndInverseSortTaggedServices('oro_calendar.calendar_provider', 'alias', $container);

        $container->getDefinition('oro_calendar.calendar_manager')
            ->setArgument(0, array_keys($services))
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
