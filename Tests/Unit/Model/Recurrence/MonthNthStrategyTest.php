<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\MonthNthStrategy;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

class MonthNthStrategyTest extends AbstractTestStrategy
{
    /** @var MonthNthStrategy  */
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

        $this->strategy = new MonthNthStrategy($translator, $dateTimeFormatter, $localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals('recurrence_monthnth', $this->strategy->getName());
    }

    public function testSupports()
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTH_N_TH);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    /**
     * @dataProvider recurrencePatternsDataProvider
     */
    public function testGetTextValue(array $recurrenceData, string $expected)
    {
        $startDate = new \DateTime($recurrenceData['startTime'], $this->getTimeZone());
        $endDate = $recurrenceData['endTime'] === null
            ? null
            : new \DateTime($recurrenceData['endTime'], $this->getTimeZone());

        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTH_N_TH)
            ->setInterval($recurrenceData['interval'])
            ->setInstance($recurrenceData['instance'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setStartTime($startDate)
            ->setTimeZone($recurrenceData['timeZone'])
            ->setEndTime($endDate)
            ->setOccurrences($recurrenceData['occurrences']);

        $calendarEvent = new Entity\CalendarEvent();
        $calendarEvent->setStart(new \DateTime($recurrenceData['startTime']));
        $recurrence->setCalendarEvent($calendarEvent);

        $this->assertStringStartsWith($expected, $this->strategy->getTextValue($recurrence));
    }

    /**
     * @dataProvider recurrenceLastOccurrenceDataProvider
     */
    public function testGetCalculatedEndTime(array $recurrenceData, \DateTime $expected)
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTH_N_TH)
            ->setInterval($recurrenceData['interval'])
            ->setInstance($recurrenceData['instance'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setStartTime(new \DateTime($recurrenceData['startTime'], $this->getTimeZone()))
            ->setTimeZone('UTC')
            ->setOccurrences($recurrenceData['occurrences']);

        if (!empty($recurrenceData['endTime'])) {
            $recurrence->setEndTime(new \DateTime($recurrenceData['endTime'], $this->getTimeZone()));
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
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-02-01',
                    'end' => '2016-04-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-08-01',
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
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                ],
            ],
            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end' => [
                'params' => [
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-04-01',
                    'end' => '2016-09-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-08-01',
                ],
                'expected' => [
                    '2016-06-06',
                    '2016-08-01',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end' => [
                'params' => [
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-06-06',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-09-01',
                    'end' => '2016-11-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-08-01',
                ],
                'expected' => [
                ],
            ],

            'start < startTime < end < endTime with X instance' => [
                'params' => [
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_LAST,
                    'interval' => 2,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-10',
                ],
                'expected' => [
                    '2016-04-25',
                ],
            ],
            'start < startTime < end < endTime with X occurrence' => [
                'params' => [
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_LAST,
                    'interval' => 2,
                    'occurrences' => 2,
                    'start' => '2016-07-25',
                    'end' => '2016-09-04',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-12-31',
                ],
                'expected' => [
                ],
            ],
            'start < startTime < end < endTime with X occurrence, weekend day' => [
                'params' => [
                    'daysOfWeek' => [
                        'saturday',
                        'sunday'
                    ],
                    'instance' => Recurrence::INSTANCE_THIRD,
                    'interval' => 2,
                    'occurrences' => 2,
                    'start' => '2016-06-01',
                    'end' => '2016-06-30',
                    'startTime' => '2016-04-01',
                    'endTime' => '2016-12-31',
                ],
                'expected' => [
                    '2016-06-11'
                ],
            ],
            'start = startTime < end < endTime with X occurrence' => [
                'params' => [
                    'daysOfWeek' => [
                        'monday',
                    ],
                    'instance' => Recurrence::INSTANCE_LAST,
                    'interval' => 1,
                    'occurrences' => 10,
                    'start' => '2016-06-27 07:00:00',
                    'end' => '2016-08-04 07:00:00',
                    'startTime' => '2016-06-27 07:00:00',
                    'endTime' => '2016-12-31 07:00:00',
                ],
                'expected' => [
                    '2016-06-27 07:00:00',
                    '2016-07-25 07:00:00'
                ],
            ],
            'start < startTime < endTime < end with last day' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                    ],
                    'instance' => Recurrence::INSTANCE_LAST,
                    'interval' => 1,
                    'occurrences' => null,
                    'start' => '2014-01-26',
                    'end' => '2017-05-06',
                    'startTime' => '2014-02-28',
                    'endTime' => '2017-12-31',
                ],
                'expected' => [
                    '2014-02-28',
                    '2014-03-31',
                    '2014-04-30',
                    '2014-05-31',
                    '2014-06-30',
                    '2014-07-31',
                    '2014-08-31',
                    '2014-09-30',
                    '2014-10-31',
                    '2014-11-30',
                    '2014-12-31',
                    '2015-01-31',
                    '2015-02-28',
                    '2015-03-31',
                    '2015-04-30',
                    '2015-05-31',
                    '2015-06-30',
                    '2015-07-31',
                    '2015-08-31',
                    '2015-09-30',
                    '2015-10-31',
                    '2015-11-30',
                    '2015-12-31',
                    '2016-01-31',
                    '2016-02-29',
                    '2016-03-31',
                    '2016-04-30',
                    '2016-05-31',
                    '2016-06-30',
                    '2016-07-31',
                    '2016-08-31',
                    '2016-09-30',
                    '2016-10-31',
                    '2016-11-30',
                    '2016-12-31',
                    '2017-01-31',
                    '2017-02-28',
                    '2017-03-31',
                    '2017-04-30',
                ],
            ],
            'start < startTime < endTime < end with first day' => [
                'params' => [
                    'daysOfWeek' => [
                        'sunday',
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                    ],
                    'instance' => Recurrence::INSTANCE_FIRST,
                    'interval' => 1,
                    'occurrences' => null,
                    'start' => '2014-01-26',
                    'end' => '2017-05-06',
                    'startTime' => '2014-02-28',
                    'endTime' => '2017-12-31',
                ],
                'expected' => [
                    '2014-03-01',
                    '2014-04-01',
                    '2014-05-01',
                    '2014-06-01',
                    '2014-07-01',
                    '2014-08-01',
                    '2014-09-01',
                    '2014-10-01',
                    '2014-11-01',
                    '2014-12-01',
                    '2015-01-01',
                    '2015-02-01',
                    '2015-03-01',
                    '2015-04-01',
                    '2015-05-01',
                    '2015-06-01',
                    '2015-07-01',
                    '2015-08-01',
                    '2015-09-01',
                    '2015-10-01',
                    '2015-11-01',
                    '2015-12-01',
                    '2016-01-01',
                    '2016-02-01',
                    '2016-03-01',
                    '2016-04-01',
                    '2016-05-01',
                    '2016-06-01',
                    '2016-07-01',
                    '2016-08-01',
                    '2016-09-01',
                    '2016-10-01',
                    '2016-11-01',
                    '2016-12-01',
                    '2017-01-01',
                    '2017-02-01',
                    '2017-03-01',
                    '2017-04-01',
                    '2017-05-01',
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
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthnth2oro.calendar.recurrence.days'
                    . '.sundayoro.calendar.recurrence.instances.third'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthnth2oro.calendar.recurrence.days'
                    . '.sundayoro.calendar.recurrence.instances.thirdoro.calendar.recurrence.patterns.occurrences3'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthnth2oro.calendar.recurrence.days.'
                    . 'sundayoro.calendar.recurrence.instances.thirdoro.calendar.recurrence.patterns.end_date'
            ],
            'with_timezone' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28T04:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'America/Los_Angeles',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthnth2oro.calendar.recurrence.days'
                    . '.sundayoro.calendar.recurrence.instances.thirdoro.calendar.recurrence.patterns.timezone'
            ],
        ];
    }

    public function recurrenceLastOccurrenceDataProvider(): array
    {
        return [
            'without_end_date' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                ],
                'expected' => new \DateTime(Recurrence::MAX_END_DATE, $this->getTimeZone())
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-05-12',
                    'occurrences' => null,
                ],
                'expected' => new \DateTime('2016-05-12', $this->getTimeZone())
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 1,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2016-10-02', $this->getTimeZone())
            ],
            'with_occurrences_1' => [
                'params' => [
                    'interval' => 1,
                    'instance' => 2,
                    'dayOfWeek' => ['saturday', 'sunday'],
                    'startTime' => '2016-04-02',
                    'endTime' => null,
                    'occurrences' => 10,
                ],
                'expected' => new \DateTime('2017-01-07', $this->getTimeZone())
            ],
            'with_occurrences_2' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 4,
                    'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday',],
                    'startTime' => '2016-04-02',
                    'endTime' => null,
                    'occurrences' => 5,
                ],
                'expected' => new \DateTime('2016-12-06', $this->getTimeZone())
            ]
        ];
    }

    protected function getType(): string
    {
        return Recurrence::TYPE_MONTH_N_TH;
    }
}
