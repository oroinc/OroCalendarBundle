<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * The abstract test contains validation errors cases triggered by create/update calendar event.
 */
abstract class AbstractValidationErrorTestCase extends AbstractTestCase
{
    /**
     * @return array
     */
    public function recurrenceValidationFailedDataProvider()
    {
        return [
            'test' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 0,
                    'instance' => null,
                    'dayOfWeek' => [],
                    'dayOfMonth' => null,
                    'monthOfYear' => null,
                    'startTime' => gmdate(DATE_RFC3339),
                    'endTime' => null,
                    'occurrences' => null,
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'interval' => [
                        'This value should be 1 or more.'
                    ]
                ]
            ],
            'recurrenceType is not provided' => [
                'recurrence' => [
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'recurrenceType' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'recurrenceType is blank' => [
                'recurrence' => [
                    'recurrenceType' => null,
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'recurrenceType' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'recurrenceType has invalid type' => [
                'recurrence' => [
                    'interval' => 1,
                    'recurrenceType' => [1],
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'recurrenceType' => [
                        'This value is not valid.'
                    ]
                ]
            ],
            'recurrenceType is invalid' => [
                'recurrence' => [
                    'recurrenceType' => 'unknown',
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'recurrenceType' => [
                        'This value is not valid.'
                    ]
                ]
            ],
            'interval is blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => null,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'interval is not provided' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'interval' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'interval has wrong type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 'string',
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC',
                ],
                'errors' => [
                    'interval' => [
                        'This value is not valid.'
                    ]
                ]
            ],
            'interval is too small' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 0,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
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
                    'interval' => 100,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'UTC'
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
                    'interval' => 12000,
                    'startTime' => gmdate(DATE_RFC3339),
                    'dayOfMonth' => 1,
                    'monthOfYear' => 1,
                    'timeZone' => 'UTC'
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
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'dayOfMonth' => 1,
                    'monthOfYear' => 1,
                    'timeZone' => 'UTC'
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
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'dayOfWeek' => ['monday'],
                    'monthOfYear' => 1,
                    'timeZone' => 'UTC'
                ],
                'errors' => [
                    'interval' => [
                        'This value should be a multiple of 12.'
                    ]
                ]
            ],
            'timeZone is not provided' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                ],
                'errors' => [
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'timeZone is blank' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => ''
                ],
                'errors' => [
                    'timeZone' => [
                        'This value should not be blank.'
                    ]
                ]
            ],
            'timeZone has invalid type' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => ['UTC']
                ],
                'errors' => [
                    'timeZone' => [
                        'This value is not valid.'
                    ]
                ]
            ],
            'timeZone is invalid' => [
                'recurrence' => [
                    'recurrenceType' => Recurrence::TYPE_DAILY,
                    'interval' => 1,
                    'startTime' => gmdate(DATE_RFC3339),
                    'timeZone' => 'unknown'
                ],
                'errors' => [
                    'timeZone' => [
                        'This value is not valid.'
                    ]
                ]
            ],
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
