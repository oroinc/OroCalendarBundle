<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroCalendarExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'oro_calendar.enabled_system_calendar',
            $config['enabled_system_calendar']
        );

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form.yml');
        $loader->load('controllers.yml');
    }
}
