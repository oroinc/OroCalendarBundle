<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\DailyStrategy;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

class DailyStrategyTest extends AbstractTestStrategy
{
    /** @var DailyStrategy */
    protected $strategy;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $parameters = []) {
                return $id . implode($parameters);
            });
        $dateTimeFormatter = $this->createMock(DateTimeFormatterInterface::class);

        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->willReturn('UTC');

        $this->strategy = new DailyStrategy($translator, $dateTimeFormatter, $localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals('recurrence_daily', $this->strategy->getName());
    }

    public function testSupports()
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_DAILY);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    /**
     * @dataProvider recurrencePatternsDataProvider
     */
    public function testGetTextValue(array $recurrenceData, string $expected)
    {
        $startDate = new \DateTime($recurrenceData['startTime']);
        $endDate = $recurrenceData['endTime'] === null ? null : new \DateTime($recurrenceData['endTime']);

        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_DAILY)
            ->setInterval($recurrenceData['interval'])
            ->setStartTime($startDate)
            ->setTimeZone($recurrenceData['timeZone'])
            ->setEndTime($endDate)
            ->setOccurrences($recurrenceData['occurrences']);

        $calendarEvent = new Entity\CalendarEvent();
        $calendarEvent->setStart($startDate);
        $recurrence->setCalendarEvent($calendarEvent);

        $this->assertStringStartsWith($expected, $this->strategy->getTextValue($recurrence));
    }

    /**
     * @dataProvider recurrenceLastOccurrenceDataProvider
     */
    public function testGetCalculatedEndTime(array $recurrenceData, \DateTime $expected)
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_DAILY)
            ->setInterval($recurrenceData['interval'])
            ->setStartTime(new \DateTime($recurrenceData['startTime']))
            ->setTimeZone('UTC')
            ->setOccurrences($recurrenceData['occurrences']);

        if (!empty($recurrenceData['endTime'])) {
            $recurrence->setEndTime(new \DateTime($recurrenceData['endTime']));
        }

        $this->assertEquals($expected, $this->strategy->getCalculatedEndTime($recurrence));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function propertiesDataProvider(): array
    {
        return [
            /**
             * |-----|
             *         |-----|
             */
            'start < end < startTime < endTime' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-04-17',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],
            /**
             * |-----|
             *   |-----|
             */
            'start < startTime < end < endTime' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-04-25',
                    '2016-04-30',
                ],
            ],
            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-05-30',
                    '2016-06-04',
                    '2016-06-09',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-30',
                    'end' => '2016-05-30',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-05-10',
                ],
                'expected' => [
                    '2016-04-30',
                    '2016-05-05',
                    '2016-05-10',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-06-11',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],

            'start = end = startTime = endTime' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-24',
                    'end' => '2016-04-24',
                    'startTime' => '2016-04-24',
                    'endTime' => '2016-04-24',
                ],
                'expected' => [
                    '2016-04-24',
                ],
            ],
            'start = end = (startTime + 1 day) = (endTime + 1 day)' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-24',
                    'end' => '2016-04-24',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-04-25',
                ],
                'expected' => [
                ],
            ],
            'startTime = endTime = (start + interval days) = (end + interval days)' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => null,
                    'start' => '2016-04-30',
                    'end' => '2016-04-30',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-04-25',
                ],
                'expected' => [
                ],
            ],
            'start < startTime < endTime < end after x occurrences' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => 8,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-05-30',
                ],
            ],
            'start < startTime < endTime < end after y occurrences' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => 7,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],
            'no endTime' => [
                'params' => [
                    'interval' => 5,
                    'occurrences' => 8,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '9999-12-31',
                ],
                'expected' => [
                    '2016-05-30',
                ],
            ],
        ];
    }

    public function recurrencePatternsDataProvider(): array
    {
        return [
            'without_occurrences_and_end_date' => [
                'params' => [
                    'interval' => 2,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.daily2'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.daily2oro.calendar.recurrence.patterns.occurrences3'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.daily2oro.calendar.recurrence.patterns.end_date'
            ],
            'with_timezone' => [
                'params' => [
                    'interval' => 2,
                    'startTime' => '2016-04-28T04:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'America/Los_Angeles'
                ],
                'expected' => 'oro.calendar.recurrence.patterns.daily2oro.calendar.recurrence.patterns.timezone'
            ]
        ];
    }

    public function recurrenceLastOccurrenceDataProvider(): array
    {
        return [
            'without_end_date' => [
                'params' => [
                    'interval' => 2,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                ],
                'expected' => new \DateTime(Recurrence::MAX_END_DATE)
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-05-12',
                    'occurrences' => null,
                ],
                'expected' => new \DateTime('2016-05-12')
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 5,
                ],
                'expected' => new \DateTime('2016-05-06')
            ]
        ];
    }

    protected function getType(): string
    {
        return Recurrence::TYPE_DAILY;
    }
}
