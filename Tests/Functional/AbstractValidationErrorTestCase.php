<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * The abstract test contains validation errors cases triggered by create/update calendar event.
 */
abstract class AbstractValidationErrorTestCase extends AbstractTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function recurrenceValidationFailedDataProvider(): array
    {
        $startTime = gmdate(DATE_RFC3339);
        $wrongEndTime = gmdate(DATE_RFC3339, strtotime('-1 day'));

        return [
            // Validation cases for "recurrenceType" field
            'recurrenceType has invalid type' => [
                'recurrence' => [
                    'interval' => 1,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'recurrenceType' => [1],
                ],
                'errors' => [
                    'recurrenceType' => [
                        'The selected choice is invalid.'
                    ]
                ]
            ],
            'recurrenceType is invalid' => [
                'recurrence' => [
                    'interval' => 1,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'recurrenceType' => 'unknown',
                ],
                'errors' => [
                    'recurrenceType' => [
                        'The selected choice is invalid.'
                    ]
                ]
            ],
            // Validation cases for "endTime" field
            'endTime greater then startTime' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'timeZone' => 'UTC',
                    'interval' => 1,
                    'startTime' => $startTime,
                    'endTime' => $wrongEndTime,
                ],
                'errors' => [
                    'endTime' => [
                        sprintf(
                            'This value should be %s or more.',
                            $startTime
                        )
                    ]
                ]
            ],
            'endTime has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'timeZone' => 'UTC',
                    'interval' => 1,
                    'startTime' => $startTime,
                    'endTime' => 'string',
                ],
                'errors' => [
                    'endTime' => [
                        'Please enter a valid date and time.'
                    ]
                ]
            ],
            // Validation cases for "occurrences" field
            'occurrences has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'timeZone' => 'UTC',
                    'interval' => 1,
                    'startTime' => $startTime,
                    'occurrences' => 'string',
                ],
                'errors' => [
                    'occurrences' => [
                        'Please enter an integer.'
                    ]
                ]
            ],
            'occurrences is too small' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'timeZone' => 'UTC',
                    'interval' => 1,
                    'startTime' => $startTime,
                    'occurrences' => 0,
                ],
                'errors' => [
                    'occurrences' => [
                        'This value should be between 1 and 999.'
                    ]
                ]
            ],
            'occurrences is too big' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'timeZone' => 'UTC',
                    'interval' => 1,
                    'startTime' => $startTime,
                    'occurrences' => 1000,
                ],
                'errors' => [
                    'occurrences' => [
                        'This value should be between 1 and 999.'
                    ]
                ]
            ],
            // Validation cases for "interval" field
            'interval has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'interval' => 'string',
                ],
                'errors' => [
                    'interval' => [
                        'Please enter an integer.'
                    ]
                ]
            ],
            'interval is too small' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'interval' => 0,
                ],
                'errors' => [
                    'interval' => [
                        'This value should be 1 or more.'
                    ]
                ]
            ],
            'interval is too big for daily recurrence type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'interval' => 100,
                ],
                'errors' => [
                    'interval' => [
                        'This value should be 99 or less.'
                    ]
                ]
            ],
            'interval is too big for yearly recurrence type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                    'startTime' => $startTime,
                    'dayOfMonth' => 1,
                    'monthOfYear' => 1,
                    'timeZone' => 'UTC',
                    'interval' => 12000,
                ],
                'errors' => [
                    'interval' => [
                        'This value should be 999 or less.'
                    ]
                ]
            ],
            'interval is not multiple of 12 for yearly recurrence type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                    'startTime' => $startTime,
                    'dayOfMonth' => 1,
                    'monthOfYear' => 1,
                    'timeZone' => 'UTC',
                    'interval' => 1,
                ],
                'errors' => [
                    'interval' => [
                        'This value should be a multiple of 12.'
                    ]
                ]
            ],
            'interval is not multiple of 12 for yearlynth recurrence type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEAR_N_TH,
                    'instance' => 1,
                    'startTime' => $startTime,
                    'dayOfWeek' => ['monday'],
                    'monthOfYear' => 1,
                    'timeZone' => 'UTC',
                    'interval' => 1,
                ],
                'errors' => [
                    'interval' => [
                        'This value should be a multiple of 12.'
                    ]
                ]
            ],
            // Validation cases for "timeZone" field
            'timeZone has invalid type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'startTime' => $startTime,
                    'timeZone' => ['UTC']
                ],
                'errors' => [
                    'timeZone' => [
                        'Please select a valid timezone.'
                    ]
                ]
            ],
            'timeZone is invalid' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'startTime' => $startTime,
                    'timeZone' => 'unknown'
                ],
                'errors' => [
                    'timeZone' => [
                        'Please select a valid timezone.'
                    ]
                ]
            ],
            // Validation cases for "instance" field
            'instance has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                    'interval' => 1,
                    'dayOfWeek' => ['monday'],
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'instance' => 'string',
                ],
                'errors' => [
                    'instance' => [
                        'The selected choice is invalid.'
                    ]
                ]
            ],
            'instance is too small' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                    'interval' => 1,
                    'dayOfWeek' => ['monday'],
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'instance' => 0,
                ],
                'errors' => [
                    'instance' => [
                        // Choice field of "instance" doesn't accept values out of the list.
                        'The selected choice is invalid.',
                    ]
                ]
            ],
            'instance is too big' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                    'interval' => 1,
                    'dayOfWeek' => ['monday'],
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'instance' => 6,
                ],
                'errors' => [
                    'instance' => [
                        // Choice field of "instance" doesn't accept values out of the list.
                        'The selected choice is invalid.',
                    ]
                ]
            ],
            // Validation cases for "dayOfWeek" field
            'dayOfWeek has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                    'interval' => 1,
                    'instance' => 1,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'dayOfWeek' => 'string',
                ],
                'errors' => [
                    'dayOfWeek' => [
                        'The selected choice is invalid.'
                    ]
                ]
            ],
            'dayOfWeek is invalid' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                    'interval' => 1,
                    'instance' => 1,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'dayOfWeek' => ['Monday'],
                ],
                'errors' => [
                    'dayOfWeek' => [
                        'The selected choice is invalid.',
                        'This value should not be blank.',
                    ]
                ]
            ],
            // Validation cases for "dayOfMonth" field
            'dayOfMonth has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTHLY,
                    'interval' => 1,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'dayOfMonth' => 'string',
                ],
                'errors' => [
                    'dayOfMonth' => [
                        'Please enter an integer.'
                    ]
                ]
            ],
            'dayOfMonth too small' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTHLY,
                    'interval' => 1,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'dayOfMonth' => 0,
                ],
                'errors' => [
                    'dayOfMonth' => [
                        'This value should be between 1 and 31.'
                    ]
                ]
            ],
            'dayOfMonth too big' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                    'interval' => 12,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'monthOfYear' => 11,
                    'dayOfMonth' => 32,
                ],
                'errors' => [
                    'dayOfMonth' => [
                        'This value should be between 1 and 31.'
                    ]
                ]
            ],
            // Validation cases for "monthOfYear" field
            'monthOfYear has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                    'interval' => 12,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'dayOfMonth' => 1,
                    'monthOfYear' => 'string',
                ],
                'errors' => [
                    'monthOfYear' => [
                        'Please enter an integer.'
                    ]
                ]
            ],
            'monthOfYear too small' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                    'interval' => 12,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'dayOfMonth' => 1,
                    'monthOfYear' => 0,
                ],
                'errors' => [
                    'monthOfYear' => [
                        'This value should be between 1 and 12.'
                    ]
                ]
            ],
            'monthOfYear too big' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                    'interval' => 12,
                    'startTime' => $startTime,
                    'timeZone' => 'UTC',
                    'dayOfMonth' => 1,
                    'monthOfYear' => 13,
                ],
                'errors' => [
                    'monthOfYear' => [
                        'This value should be between 1 and 12.'
                    ]
                ]
            ],
            // Validation cases for requied fields depending on "recurrenceType" field
            'daily type has required fields blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ],
                    'startTime' => [
                        'This value should not be blank.'
                    ],
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'weekly type has required fields blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_WEEKLY,
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ],
                    'dayOfWeek' => [
                        'This value should not be blank.'
                    ],
                    'startTime' => [
                        'This value should not be blank.'
                    ],
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'monthly type has required fields blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTHLY,
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ],
                    'dayOfMonth' => [
                        'This value should not be blank.'
                    ],
                    'startTime' => [
                        'This value should not be blank.'
                    ],
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'monthlynth type has required fields blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_MONTH_N_TH,
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ],
                    'instance' => [
                        'This value should not be blank.'
                    ],
                    'dayOfWeek' => [
                        'This value should not be blank.'
                    ],
                    'startTime' => [
                        'This value should not be blank.'
                    ],
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'yearly type has required fields blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEARLY,
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ],
                    'dayOfMonth' => [
                        'This value should not be blank.'
                    ],
                    'monthOfYear' => [
                        'This value should not be blank.'
                    ],
                    'startTime' => [
                        'This value should not be blank.'
                    ],
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'yearlynth type has required fields blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_YEAR_N_TH,
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ],
                    'instance' => [
                        'This value should not be blank.'
                    ],
                    'dayOfWeek' => [
                        'This value should not be blank.'
                    ],
                    'monthOfYear' => [
                        'This value should not be blank.'
                    ],
                    'startTime' => [
                        'This value should not be blank.'
                    ],
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
        ];
    }
}
