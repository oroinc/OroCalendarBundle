<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class YearlyRecurringEventTest extends AbstractUseCaseTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->loadFixtures([LoadUserData::class], true);
    }

    /**
     * Test for event with pattern in Outlook "Monthly Day 31 of every 12 months Start Wed 11/30/2016 No end date".
     */
    public function testCreate()
    {
        $startDate = '2016-11-30T09:00:00+00:00';
        $endDate = '2016-11-30T09:30:00+00:00';
        $this->addCalendarEventViaAPI(
            [
                'title'       => 'Yearly Recurring Event',
                'description' => 'Yearly Recurring Event Description',
                'allDay'      => false,
                'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
                'start'       => $startDate,
                'end'         => $endDate,
                'recurrence'  => [
                    'timeZone'       => 'UTC',
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                    'interval'       => 12,
                    'monthOfYear'    => 11,
                    'dayOfMonth'     => 31,
                    'startTime'      => $startDate,
                    'occurrences'    => null,
                    'endTime'        => null,
                ],
                'attendees'   => [],
            ]
        );

        $request = [
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => '2016-01-01T00:00:00+00:00',
            'end'         => '2018-01-01T00:00:00+00:00',
            'subordinate' => true,
        ];
        $actualEvents = $this->getOrderedCalendarEventsViaAPI($request);

        $this->assertCalendarEvents(
            [
                // order of position does matter
                [
                    'start' => '2017-11-30T09:00:00+00:00',
                    'end'   => '2017-11-30T09:30:00+00:00',
                ],
                [
                    'start' => '2016-11-30T09:00:00+00:00',
                    'end'   => '2016-11-30T09:30:00+00:00',
                ],
            ],
            $actualEvents
        );
    }
}
