<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Twig\DateFormatExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DateFormatExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var DateFormatExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = $this->getMockBuilder(DateTimeFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.date_time', $this->formatter)
            ->add('oro_config.global', $this->configManager)
            ->getContainer($this);

        $this->extension = new DateFormatExtension($container);
    }

    /**
     * @param string $start
     * @param string|bool $end
     * @param string|bool $skipTime
     * @param string $expected
     *
     * @dataProvider formatCalendarDateRangeProvider
     */
    public function testFormatCalendarDateRange($start, $end, $skipTime, $expected)
    {
        $startDate = new \DateTime($start);
        $endDate = $end === null ? null : new \DateTime($end);

        $this->formatter->expects($this->any())
            ->method('format')
            ->will($this->returnValue('DateTime'));
        $this->formatter->expects($this->any())
            ->method('formatDate')
            ->will($this->returnValue('Date'));
        $this->formatter->expects($this->any())
            ->method('formatTime')
            ->will($this->returnValue('Time'));

        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'calendar_date_range', [$startDate, $endDate, $skipTime])
        );
    }

    public function formatCalendarDateRangeProvider()
    {
        return array(
            array('2010-05-01T10:30:15+00:00', null, false, 'DateTime'),
            array('2010-05-01T10:30:15+00:00', null, true, 'Date'),
            array('2010-05-01T10:30:15+00:00', '2010-05-01T10:30:15+00:00', false, 'DateTime'),
            array('2010-05-01T10:30:15+00:00', '2010-05-01T10:30:15+00:00', true, 'Date'),
            array('2010-05-01T10:30:15+00:00', '2010-05-01T11:30:15+00:00', false, 'Date Time - Time'),
            array('2010-05-01T10:30:15+00:00', '2010-05-01T11:30:15+00:00', true, 'Date'),
            array('2010-05-01T10:30:15+00:00', '2010-05-02T10:30:15+00:00', false, 'DateTime - DateTime'),
            array('2010-05-01T10:30:15+00:00', '2010-05-02T10:30:15+00:00', true, 'Date - Date'),
        );
    }

    /**
     * @param string $start
     * @param string $end
     * @param array $config
     * @param string|null $locale
     * @param string|null $timeZone
     * @param Organization $organization
     *
     * @dataProvider formatCalendarDateRangeOrganizationProvider
     */
    public function testFormatCalendarDateRangeOrganization(
        $start,
        $end,
        array $config,
        $locale,
        $timeZone,
        $organization
    ) {
        $startDate = new \DateTime($start);
        $endDate = $end === null ? null : new \DateTime($end);

        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_locale.locale', false, false, $config['locale']],
                        ['oro_locale.timezone', false, false, $config['timeZone']],
                    ]
                )
            );

        self::callTwigFunction(
            $this->extension,
            'calendar_date_range_organization',
            [
                $startDate,
                $endDate,
                false,
                null,
                null,
                $locale,
                $timeZone,
                $organization
            ]
        );

        $this->configManager->expects($this->never())
            ->method('get');

        self::callTwigFunction(
            $this->extension,
            'calendar_date_range_organization',
            [
                $startDate,
                $endDate,
                false,
                null,
                null,
                $locale,
                $timeZone
            ]
        );
    }

    public function formatCalendarDateRangeOrganizationProvider()
    {
        $organization = new Organization(1);
        return [
            'Localization settings from global scope' => [
                '2016-05-01T10:30:15+00:00',
                '2016-05-01T11:30:15+00:00',
                ['locale' => 'en_US', 'timeZone' => 'UTC'], // config global scope
                null,
                null,
                $organization
            ],
            'Localization settings from params values' => [
                '2016-05-01T10:30:15+00:00',
                '2016-05-01T11:30:15+00:00',
                ['locale' => 'en_US', 'timeZone' => 'UTC'], // config global scope
                'en_US',
                'Europe/Athens',
                null
            ]
        ];
    }
}
