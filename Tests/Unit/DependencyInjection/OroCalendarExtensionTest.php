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
                                '#AC725E',
                                '#D06B64',
                                '#F83A22',
                                '#FA573C',
                                '#FF7537',
                                '#FFAD46',
                                '#42D692',
                                '#16A765',
                                '#7BD148',
                                '#B3DC6C',
                                '#FBE983',
                                '#FAD165',
                                '#92E1C0',
                                '#9FE1E7',
                                '#9FC6E7',
                                '#4986E7',
                                '#9A9CFF',
                                '#B99AFF',
                                '#C2C2C2',
                                '#CABDBF',
                                '#CCA6AC',
                                '#F691B2',
                                '#CD74E6',
                                '#A47AE2'
                            ],
                            'scope' => 'app'
                        ],
                        'event_colors' => [
                            'value' => [
                                '#5484ED',
                                '#A4BDFC',
                                '#46D6DB',
                                '#7AE7BF',
                                '#51B749',
                                '#FBD75B',
                                '#FFB878',
                                '#FF887C',
                                '#DC2127',
                                '#DBADFF',
                                '#E1E1E1'
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
