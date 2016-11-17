define(function(require) {
    'use strict';

    var EventRecurrenceModel;
    var _ = require('underscore');
    var localeSettings = require('orolocale/js/locale-settings');
    var BaseModel = require('oroui/js/app/models/base/model');

    EventRecurrenceModel = BaseModel.extend({
        RECURRENCE_TYPES: ['daily', 'weekly', 'monthly', 'monthnth', 'yearly', 'yearnth'],
        RECURRENCE_INSTANCE: {1: 'first', 2: 'second', 3: 'third', 4: 'fourth', 5: 'last'},
        RECURRENCE_DAYOFWEEK: localeSettings.getCalendarDayOfWeekNames('mnemonic', true),
        RECURRENCE_MONTHS: localeSettings.getCalendarMonthNames('wide'),

        defaults: {
            recurrenceType: null, // one of RECURRENCE_TYPES
            interval: 1, // int
            instance: null, // one of number 1-5 (see RECURRENCE_INSTANCE)
            dayOfWeek: [], // array of ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"]
            dayOfMonth: null, // int
            monthOfYear: null, // int
            startTime: null,
            endTime: null, // date in ISO format
            occurrences: null, // int
            timeZone: localeSettings.getTimeZone()
        },

        isEmptyRecurrence: function() {
            return this.RECURRENCE_TYPES.indexOf(this.get('recurrenceType')) === -1;
        }
    });

    return EventRecurrenceModel;
});
