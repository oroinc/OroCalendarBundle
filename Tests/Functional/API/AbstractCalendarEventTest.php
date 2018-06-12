<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

/**
 * @deprecated Use \Oro\Bundle\CalendarBundle\Tests\Functional\AbstractTestCase instead.
 */
abstract class AbstractCalendarEventTest extends WebTestCase
{
    const DATE_RANGE_START = '-5 day';
    const DATE_RANGE_END = '+5 day';

    const DEFAULT_USER_CALENDAR_ID = 1;
    
    /** @var array */
    protected static $regularEventParameters;

    /** @var array */
    protected static $recurringEventParameters;

    /** @var array */
    protected static $recurringEventExceptionParameters;

    /**
     * @var bool
     */
    protected static $testDataInitialized;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            LoadCalendarEventData::class,
            LoadActivityTargets::class,
            LoadUserData::class,
        ]);

        $this->initializeTestData();
    }

    protected function initializeTestData()
    {
        if (self::$testDataInitialized) {
            return;
        }

        $targetOne = $this->getReference('activity_target_one');

        self::$regularEventParameters = [
            'title' => 'Test Regular Event',
            'description' => 'Test Regular Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
        ];
        self::$recurringEventParameters = [
            'title' => 'Test Recurring Event',
            'description' => 'Test Recurring Event Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => [],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => gmdate(DATE_RFC3339),
                'endTime' => null,
                'occurrences' => null,
                'timeZone' => 'UTC'
            ],
            'attendees' => [
                [
                    'email' => 'system_user_2@example.com',
                ],
            ],
            'contexts' => json_encode([
                'entityId' => $targetOne->getId(),
                'entityClass' => get_class($targetOne),
            ])
        ];
        self::$recurringEventExceptionParameters = [
            'title' => 'Test Recurring Event Exception',
            'description' => 'Test Recurring Exception Description',
            'start' => gmdate(DATE_RFC3339),
            'end' => gmdate(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => $this->getReference('oro_calendar:calendar:system_user_1')->getId(),
            'recurringEventId' => -1, // is set dynamically
            'originalStart' => gmdate(DATE_RFC3339),
            'isCancelled' => true,
            'attendees' => [
                [
                    'email' => 'system_user_2@example.com',
                ],
            ],
        ];

        self::$testDataInitialized = true;
    }

    public static function tearDownAfterClass()
    {
        self::$testDataInitialized = false;
    }
}
