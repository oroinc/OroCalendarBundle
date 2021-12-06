<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API\Rest;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\AbstractValidationErrorTestCase;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * The test covers validation errors triggered in calendar events API.
 *
 * Use cases covered:
 * - Create recurring calendar event with invalid fields of recurrence.
 */
class ValidationFailedTest extends AbstractValidationErrorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadUserData::class]);
    }

    /**
     * Create recurring calendar event with invalid fields of recurrence.
     *
     * Steps:
     * 1. Create regular calendar event using minimal required data in the request.
     *
     * @dataProvider recurrenceValidationFailedDataProvider
     */
    public function testCreateRecurringCalendarEventWithInvalidFieldsOfRecurrenceArray(array $recurrence, array $errors)
    {
        // Step 1. Create regular calendar event using minimal required data in the request.
        $eventData = [
            'title'      => 'Recurring event',
            'start'      => '2016-10-14T22:00:00+00:00',
            'end'        => '2016-10-14T23:00:00+00:00',
            'recurrence' => $recurrence,
            'calendar'   => 1
        ];

        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 400,
                'contentType' => 'application/json'
            ]
        );

        $calendarEvent = $this->getEntityRepository(CalendarEvent::class)
            ->findOneBy(['title' => $eventData['title']]);
        $this->assertNull($calendarEvent, 'Failed asserting the event was not created due to validation error.');

        $this->assertResponseEquals(
            $this->getValidationFailedResponse($errors),
            $response
        );
    }

    private function getValidationFailedResponse(array $recurrenceErrors = []): array
    {
        foreach ($recurrenceErrors as $recurrenceField => $errors) {
            $recurrenceErrors[$recurrenceField] = ['errors' => $errors];
        }

        return [
            'code'    => 400,
            'message' => 'Validation Failed',
            'errors'  => [
                'children' => [
                    'allDay'           => [],
                    'attendees'        => [],
                    'backgroundColor'  => [],
                    'calendar'         => [],
                    'calendarAlias'    => [],
                    'createdAt'        => [],
                    'contexts'         => [],
                    'description'      => [],
                    'end'              => [],
                    'id'               => [],
                    'uid'              => [],
                    'isCancelled'      => [],
                    'notifyAttendees'  => [],
                    'originalStart'    => [],
                    'recurrence'       => [
                        'children' => array_replace(
                            [
                                'dayOfMonth'     => [],
                                'dayOfWeek'      => [],
                                'endTime'        => [],
                                'instance'       => [],
                                'interval'       => [],
                                'monthOfYear'    => [],
                                'occurrences'    => [],
                                'recurrenceType' => [],
                                'startTime'      => [],
                                'timeZone'       => [],
                            ],
                            $recurrenceErrors
                        ),
                    ],
                    'recurringEventId'      => [],
                    'reminders'             => [],
                    'start'                 => [],
                    'title'                 => [],
                    'updateExceptions'      => [],
                    'organizerDisplayName'  => [],
                    'organizerEmail'        => []
                ],
            ],
        ];
    }

    public function testNotifyAttendeesValidationRejectRequestIfNotificationStrategyIsUnknown()
    {
        $eventData = [
            'title'           => 'Recurring event',
            'start'           => '2016-10-14T22:00:00+00:00',
            'end'             => '2016-10-14T23:00:00+00:00',
            'recurrence'      => [],
            'notifyAttendees' => 'unknown_notification_strategy',
            'calendar'        => 1
        ];

        $this->restRequest(
            [
                'method'  => 'POST',
                'url'     => $this->getUrl('oro_api_post_calendarevent'),
                'server'  => $this->generateWsseAuthHeader('foo_user_1', 'foo_user_1_api_key'),
                'content' => json_encode($eventData, JSON_THROW_ON_ERROR)
            ]
        );

        $response = $this->getRestResponseContent(
            [
                'statusCode'  => 400,
                'contentType' => 'application/json'
            ]
        );

        $calendarEvent = $this->getEntityRepository(CalendarEvent::class)
            ->findOneBy(['title' => $eventData['title']]);
        $this->assertNull($calendarEvent, 'Failed asserting the event was not created due to validation error.');

        $this->assertResponseEquals(
            [
                'code'    => 400,
                'message' => 'Validation Failed',
                'errors'  => [
                    'children' => [
                        'allDay'           => [],
                        'attendees'        => [],
                        'backgroundColor'  => [],
                        'calendar'         => [],
                        'calendarAlias'    => [],
                        'createdAt'        => [],
                        'contexts'         => [],
                        'description'      => [],
                        'end'              => [],
                        'id'               => [],
                        'uid'              => [],
                        'isCancelled'      => [],
                        'notifyAttendees'  => [
                            'errors' => ['The value you selected is not a valid choice.']
                        ],
                        'originalStart'    => [],
                        'recurrence'       => [
                            'children' => [
                                'dayOfMonth'     => [],
                                'dayOfWeek'      => [],
                                'endTime'        => [],
                                'instance'       => [],
                                'interval'       => [],
                                'monthOfYear'    => [],
                                'occurrences'    => [],
                                'recurrenceType' => [],
                                'startTime'      => [],
                                'timeZone'       => [],
                            ],
                        ],
                        'recurringEventId'      => [],
                        'reminders'             => [],
                        'start'                 => [],
                        'title'                 => [],
                        'updateExceptions'      => [],
                        'organizerDisplayName'  => [],
                        'organizerEmail'        => []
                    ],
                ],
            ],
            $response
        );
    }
}
