<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all calendar providers.
 */
class CalendarProviderPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $tagName = 'oro_calendar.calendar_provider';
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($tagName, true);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $items[$this->getPriorityAttribute($attributes)][] = [
                    $this->getRequiredAttribute($attributes, 'alias', $id, $tagName),
                    $id
                ];
            }
        }

        $services = [];
        if ($items) {
            ksort($items);
            $items = array_merge(...array_values($items));
            foreach ($items as [$key, $id]) {
                if (!isset($services[$key])) {
                    $services[$key] = new Reference($id);
                }
            }
        }

        $container->getDefinition('oro_calendar.calendar_manager')
            ->setArgument('$providerAliases', array_keys($services))
            ->setArgument('$providerContainer', ServiceLocatorTagPass::register($container, $services));
    }
}
