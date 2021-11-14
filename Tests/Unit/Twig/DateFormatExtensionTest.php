<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Twig;

use Oro\Bundle\CalendarBundle\Twig\DateFormatExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DateFormatExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var DateFormatExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.date_time', $this->formatter)
            ->add('oro_config.global', $this->configManager)
            ->add('oro_locale.manager.localization', $this->localizationManager)
            ->getContainer($this);

        $this->extension = new DateFormatExtension($container);
    }

    /**
     * @dataProvider formatCalendarDateRangeProvider
     */
    public function testFormatCalendarDateRange(string $start, ?string $end, bool $skipTime, string $expected)
    {
        $startDate = new \DateTime($start);
        $endDate = $end === null ? null : new \DateTime($end);

        $this->formatter->expects($this->any())
            ->method('format')
            ->willReturn('DateTime');
        $this->formatter->expects($this->any())
            ->method('formatDate')
            ->willReturn('Date');
        $this->formatter->expects($this->any())
            ->method('formatTime')
            ->willReturn('Time');

        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'calendar_date_range', [$startDate, $endDate, $skipTime])
        );
    }

    public function formatCalendarDateRangeProvider(): array
    {
        return [
            ['2010-05-01T10:30:15+00:00', null, false, 'DateTime'],
            ['2010-05-01T10:30:15+00:00', null, true, 'Date'],
            ['2010-05-01T10:30:15+00:00', '2010-05-01T10:30:15+00:00', false, 'DateTime'],
            ['2010-05-01T10:30:15+00:00', '2010-05-01T10:30:15+00:00', true, 'Date'],
            ['2010-05-01T10:30:15+00:00', '2010-05-01T11:30:15+00:00', false, 'Date Time - Time'],
            ['2010-05-01T10:30:15+00:00', '2010-05-01T11:30:15+00:00', true, 'Date'],
            ['2010-05-01T10:30:15+00:00', '2010-05-02T10:30:15+00:00', false, 'DateTime - DateTime'],
            ['2010-05-01T10:30:15+00:00', '2010-05-02T10:30:15+00:00', true, 'Date - Date'],
        ];
    }

    /**
     * @dataProvider formatCalendarDateRangeOrganizationProvider
     */
    public function testFormatCalendarDateRangeOrganization(
        string $start,
        string $end,
        array $config,
        ?string $locale,
        ?string $timeZone,
        ?Organization $organization
    ) {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['oro_locale.default_localization', false, false, null, 42],
                ['oro_locale.timezone', false, false, null, $config['timeZone']],
            ]);

        $this->localizationManager->expects($this->any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['formattingCode' => $config['locale']]);

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

    public function formatCalendarDateRangeOrganizationProvider(): array
    {
        $organization = new Organization();
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
