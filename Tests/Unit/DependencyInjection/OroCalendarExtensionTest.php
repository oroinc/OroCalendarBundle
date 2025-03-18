<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CalendarBundle\DependencyInjection\OroCalendarExtension;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCalendarExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        CumulativeResourceManager::getInstance()->clear();

        $extension = new OroCalendarExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertEquals(
            [
                [
                    'settings' => [
                        'resolved' => true,
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
                            ],
                            'scope' => 'app'
                        ],
                        'event_colors' => [
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
                            ],
                            'scope' => 'app'
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_calendar')
        );
    }
}
