<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_calendar');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('enabled_system_calendar')
                // please note that if you want to disable it on already working system
                // you need to take care to create a migration to clean up redundant data
                // in oro_calendar_property table
                ->info(
                    "Indicates whether Organization and/or System Calendars are enabled or not.\n"
                    . "Possible values:\n"
                    . "    true         - both organization and system calendars are enabled\n"
                    . "    false        - both organization and system calendars are disabled\n"
                    . "    organization - only organization calendar is enabled\n"
                    . "    system       - only system calendar is enabled\n"
                )
                ->validate()
                    ->ifTrue(
                        function ($v) {
                            return !(is_bool($v) || (is_string($v) && in_array($v, ['organization', 'system'])));
                        }
                    )
                    ->thenInvalid(
                        'The "enabled_system_calendar" must be boolean, "organization" or "system", given %s.'
                    )
                ->end()
                ->defaultValue('system')
            ->end()
        ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'calendar_colors' => [
                    'value' => [
                        '#A57261',
                        '#CD7B6C',
                        '#A92F1F',
                        '#CD5642',
                        '#DE703F',
                        '#E09B45',
                        '#80C4A6',
                        '#368360',
                        '#96C27C',
                        '#CADEAE',
                        '#E3D47D',
                        '#D1C15C',
                        '#ACD5C4',
                        '#9EC8CC',
                        '#8EADC7',
                        '#5978A9',
                        '#AA9FC2',
                        '#C2C2C2',
                        '#CABDBF',
                        '#CCA6AC',
                        '#AF6C82',
                        '#895E95',
                        '#7D6D94'
                    ]
                ],
                'event_colors'    => [
                    'value' => [
                        '#6D8DD4',
                        '#76AAC5',
                        '#5C9496',
                        '#99B3AA',
                        '#547C51',
                        '#C3B172',
                        '#C98950',
                        '#D28E87',
                        '#A24A4D',
                        '#A285B8',
                        '#949CA1'
                    ]
                ]
            ]
        );

        return $treeBuilder;
    }
}
