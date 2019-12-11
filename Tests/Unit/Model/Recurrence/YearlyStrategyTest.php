<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\YearlyStrategy;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Translation\Translator;

class YearlyStrategyTest extends AbstractTestStrategy
{
    /** @var YearlyStrategy  */
    protected $strategy;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $validator;

    protected function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->getMock();
        /** @var \PHPUnit\Framework\MockObject\MockObject|Translator */
        $translator = $this->createMock('Symfony\Component\Translation\Translator');
        $translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id, array $parameters = []) {
                        return $id . implode($parameters);
                    }
                )
            );
        $dateTimeFormatter = $this->createMock('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface');

        $dateTimeFormatter->expects($this->any())
            ->method('formatDay')
            ->willReturnCallback(function (\DateTime $date) {
                return $date->setTimezone(new \DateTimeZone('UTC'))->format('M d');
            });

        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(['getTimezone'])
            ->getMock();
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue('UTC'));

        $this->strategy = new YearlyStrategy($translator, $dateTimeFormatter, $localeSettings);
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_yearly');
    }

    public function testSupports()
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEARLY);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    /**
     * @param $recurrenceData
     * @param $expected
     *
     * @dataProvider recurrencePatternsDataProvider
     */
    public function testGetTextValue($recurrenceData, $expected)
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEARLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfMonth($recurrenceData['dayOfMonth'])
            ->setMonthOfYear($recurrenceData['monthOfYear'])
            ->setStartTime(new \DateTime($recurrenceData['startTime']))
            ->setTimeZone($recurrenceData['timeZone'])
            ->setEndTime($recurrenceData['endTime'] === null
                ? null
                : new \DateTime($recurrenceData['endTime'], $this->getTimeZone()))
            ->setOccurrences($recurrenceData['occurrences']);

        $calendarEvent = new Entity\CalendarEvent();
        $calendarEvent->setStart(new \DateTime($recurrenceData['startTime']));
        $recurrence->setCalendarEvent($calendarEvent);

        $this->assertStringStartsWith($expected, $this->strategy->getTextValue($recurrence));
    }

    /**
     * @param $recurrenceData
     * @param $expected
     *
     * @dataProvider recurrenceLastOccurrenceDataProvider
     */
    public function testGetCalculatedEndTime($recurrenceData, $expected)
    {
        $recurrence = new Entity\Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_YEARLY)
            ->setInterval($recurrenceData['interval'])
            ->setDayOfMonth($recurrenceData['dayOfMonth'])
            ->setMonthOfYear($recurrenceData['monthOfYear'])
            ->setStartTime(new \DateTime($recurrenceData['startTime'], $this->getTimeZone()))
            ->setTimeZone('UTC')
            ->setOccurrences($recurrenceData['occurrences']);

        if (!empty($recurrenceData['endTime'])) {
            $recurrence->setEndTime(new \DateTime($recurrenceData['endTime'], $this->getTimeZone()));
        }

        $this->assertEquals($expected, $this->strategy->getCalculatedEndTime($recurrence));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function propertiesDataProvider()
    {
        return [
            /**
             * |-----|
             *         |-----|
             */
            'start < end < startTime < endTime' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2015-03-01',
                    'end' => '2015-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
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
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2016-03-28',
                    'end' => '2016-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                    '2016-04-25',
                ],
            ],

            /**
             * |-----|
             *   |-|
             */
            'start < startTime < endTime < end' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2015-03-01',
                    'end' => '2017-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                    '2016-04-25',
                ],
            ],
            /**
             *     |-----|
             * |-----|
             */
            'startTime < start < endTime < end' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2018-01-01',
                    'end' => '2019-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2018-06-30',
                ],
                'expected' => [
                    '2018-04-25',
                ],
            ],
            /**
             *         |-----|
             * |-----|
             */
            'startTime < endTime < start < end' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2021-03-28',
                    'end' => '2021-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                ],
            ],

            'startTime < start < end < endTime with X occurrences' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => 2,
                    'start' => '2017-03-28',
                    'end' => '2017-05-01',
                    'startTime' => '2016-04-25',
                    'endTime' => '2026-12-31',
                ],
                'expected' => [
                    '2017-04-25',
                ],
            ],
            'startTime < start < endTime < end without matching' => [
                'params' => [
                    'interval' => 12, // number of months, which is a multiple of 12
                    'dayOfMonth' => 25,
                    'monthOfYear' => 4,
                    'occurrences' => null,
                    'start' => '2016-05-30',
                    'end' => '2016-07-03',
                    'startTime' => '2016-04-25',
                    'endTime' => '2016-06-30',
                ],
                'expected' => [
                ],
            ],
            // data for testing of adjusting day of month
            'monthly on day 31 of every 1 year for usual one' => [
                'params' => [
                    'interval' => 12,
                    'monthOfYear' => 2,
                    'dayOfMonth' => 31,
                    'occurrences' => null,
                    'start' => '2015-01-01',
                    'end' => '2018-01-01',
                    'startTime' => '2015-02-01',
                ],
                'expected' => [
                    '2015-02-28',
                    '2016-02-29',
                    '2017-02-28',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function recurrencePatternsDataProvider()
    {
        return [
            'without_occurrences_and_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 13,
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearly0Jun 13'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 13,
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => 3,
                    'timeZone' => 'UTC',
                ],
                'expected' =>
                    'oro.calendar.recurrence.patterns.yearly0Jun 13oro.calendar.recurrence.patterns.occurrences3'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 13,
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                    'timeZone' => 'UTC',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearly0Jun 13oro.calendar.recurrence.patterns.end_date'
            ],
            'with_timezone' => [
                'params' => [
                    'interval' => 2,
                    'dayOfMonth' => 13,
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28T04:00:00+00:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'America/Los_Angeles',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearly0Jun 13oro.calendar.recurrence.patterns.timezone'
            ],
            'with_time_offset' => [
                'params' => [
                    'interval' => 12,
                    'dayOfMonth' => 8,
                    'monthOfYear' => 1,
                    'startTime' => '2016-01-07T22:00:00-03:00',
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'Europe/Kiev',
                ],
                'expected' => 'oro.calendar.recurrence.patterns.yearly1Jan 08',
            ],
        ];
    }

    /**
     * @return array
     */
    public function recurrenceLastOccurrenceDataProvider()
    {
        return [
            'without_end_date' => [
                'params' => [
                    'interval' => 12,
                    'dayOfMonth' => 13,
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => null,
                    'occurrences' => null,
                ],
                'expected' => new \DateTime(Recurrence::MAX_END_DATE, $this->getTimeZone())
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 12,
                    'dayOfMonth' => 13,
                    'monthOfYear' => 6,
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-05-12',
                    'occurrences' => null,
                ],
                'expected' => new \DateTime('2016-05-12', $this->getTimeZone())
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 24,
                    'dayOfMonth' => 10,
                    'monthOfYear' => 4,
                    'startTime' => '2016-04-08',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2020-04-10', $this->getTimeZone())
            ],
            'with_occurrences_1' => [
                'params' => [
                    'interval' => 24,
                    'dayOfMonth' => 5,
                    'monthOfYear' => 4,
                    'startTime' => '2016-04-08',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2022-04-05', $this->getTimeZone())
            ],
            'with_occurrences_2' => [
                'params' => [
                    'interval' => 24,
                    'dayOfMonth' => 8,
                    'monthOfYear' => 4,
                    'startTime' => '2016-04-08',
                    'endTime' => null,
                    'occurrences' => 3,
                ],
                'expected' => new \DateTime('2020-04-08', $this->getTimeZone())
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return Recurrence::TYPE_YEARLY;
    }
}
