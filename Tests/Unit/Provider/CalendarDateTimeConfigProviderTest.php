<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Provider\CalendarDateTimeConfigProvider;
use Oro\Bundle\LocaleBundle\Model\Calendar;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class CalendarDateTimeConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var Calendar|\PHPUnit\Framework\MockObject\MockObject */
    private $calendar;

    /** @var CalendarDateTimeConfigProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->calendar = $this->createMock(Calendar::class);

        $this->provider = new CalendarDateTimeConfigProvider($this->localeSettings);
    }

    /**
     * @dataProvider getDateRangeProvider
     */
    public function testGetDateRange(string $current, string $start, string $end)
    {
        $this->localeSettings->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('America/New_York');

        $this->localeSettings->expects($this->once())
            ->method('getCalendar')
            ->willReturn($this->calendar);

        $this->calendar->expects($this->once())
            ->method('getFirstDayOfWeek')
            ->willReturn(1);

        $date = new \DateTime($current, new \DateTimeZone('UTC'));
        $result = $this->provider->getDateRange($date);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(\DateTime::class, $result['startDate']);
        $result['startDate']->setTimezone(new \DateTimeZone('UTC'));
        $this->assertEquals($start, $result['startDate']->format('c'));
        $this->assertInstanceOf(\DateTime::class, $result['endDate']);
        $result['endDate']->setTimezone(new \DateTimeZone('UTC'));
        $this->assertEquals($end, $result['endDate']->format('c'));
    }

    public function getDateRangeProvider(): array
    {
        return [
            ['2015-05-01T10:30:15+00:00', '2015-04-26T04:00:00+00:00', '2015-06-07T04:00:00+00:00'],
            ['2015-05-15T10:30:15+00:00', '2015-04-26T04:00:00+00:00', '2015-06-07T04:00:00+00:00'],
            ['2015-05-31T10:30:15+00:00', '2015-04-26T04:00:00+00:00', '2015-06-07T04:00:00+00:00'],
            ['2014-06-01T10:30:15+00:00', '2014-06-01T04:00:00+00:00', '2014-07-13T04:00:00+00:00'],
            ['2014-01-01T10:30:15+00:00', '2013-12-29T05:00:00+00:00', '2014-02-09T05:00:00+00:00'],
            ['2014-01-20T10:30:15+00:00', '2013-12-29T05:00:00+00:00', '2014-02-09T05:00:00+00:00'],
            ['2014-01-31T10:30:15+00:00', '2013-12-29T05:00:00+00:00', '2014-02-09T05:00:00+00:00'],
        ];
    }

    public function testGetTimezoneOffset()
    {
        $this->localeSettings->expects($this->once())
            ->method('getTimeZone')
            ->willReturn('America/New_York');

        $date = new \DateTime('2014-01-20T10:30:15+00:00', new \DateTimeZone('UTC'));

        $this->assertEquals(-300, $this->provider->getTimezoneOffset($date));
    }
}
