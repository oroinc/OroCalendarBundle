<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Model\Recurrence;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractValidationErrorTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers validation errors triggered in calendar events API.
 *
 * Operations covered:
 * - create new event with invalid data required data
 *
 * Resources used:
 * - create event (oro_api_post_calendarevent)
 *
 * @dbIsolation
 */
class ValidationErrorTest extends AbstractValidationErrorTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Create recurring calendar event with invalid fields of recurrence.
     *
     * Verify expected validation errors in the response.
     *
     * @param array $recurrence
     * @param array $errors
     *
     * @dataProvider recurrenceValidationFailedDataProvider
     */
    public function testRecurrenceValidationFailed(array $recurrence, array $errors)
    {
        // Step 1. Create regular calendar event using minimal required data in the request.
        $eventData = [
            'title' => 'Recurring event',
            'start' => '2016-10-14T22:00:00+00:00',
            'end' => '2016-10-14T23:00:00+00:00',
            'recurrence' => $recurrence,
        ];

        $this->restRequest(
            [
                'method' => 'POST',
                'url' => $this->getUrl('oro_api_post_calendarevent'),
                'server' => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData)
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode' => 400,
                'contentType' => 'application/json'
            ]
        );

        $calendarEvent = $this->getEntityRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['title' => $eventData['title']]);
        $this->assertNull($calendarEvent, 'Failed asserting the event was not created due to validation error.');

        $this->assertResponseEquals(
            $this->getValidationFailedResponse($errors),
            $response
        );
    }

    /**
     * @param array $recurrenceErrors
     * @return array
     */
    protected function getValidationFailedResponse(array $recurrenceErrors = [])
    {
        foreach ($recurrenceErrors as $recurrenceField => $errors) {
            $recurrenceErrors[$recurrenceField] = ['errors' => $errors];
        }

        return [
            'code' => 400,
            'message' => 'Validation Failed',
            'errors' => [
                'children' => [
                    'allDay' => [],
                    'attendees' => [],
                    'backgroundColor' => [],
                    'calendar' => [],
                    'calendarAlias' => [],
                    'createdAt' => [],
                    'contexts' => [],
                    'description' => [],
                    'end' => [],
                    'id' => [],
                    'isCancelled' => [],
                    'notifyInvitedUsers' => [],
                    'originalStart' => [],
                    'recurrence' => [
                        'children' => array_replace(
                            [
                                'dayOfMonth' => [],
                                'dayOfWeek' => [],
                                'endTime' => [],
                                'instance' => [],
                                'interval' => [],
                                'monthOfYear' => [],
                                'occurrences' => [],
                                'recurrenceType' => [],
                                'startTime' => [],
                                'timeZone' => [],
                            ],
                            $recurrenceErrors
                        ),
                    ],
                    'recurringEventId' => [],
                    'reminders' => [],
                    'start' => [],
                    'title' => [],
                    'updateExceptions' => [],
                ],
            ],
        ];
    }
}
