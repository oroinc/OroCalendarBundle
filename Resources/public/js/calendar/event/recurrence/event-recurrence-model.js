define(function(require) {
    'use strict';

    const _ = require('underscore');
    const localeSettings = require('orolocale/js/locale-settings');
    const DAYOFWEEK = localeSettings.getCalendarDayOfWeekNames('mnemonic', true);
    const BaseModel = require('oroui/js/app/models/base/model');

    const EventRecurrenceModel = BaseModel.extend({
        RECURRENCE_TYPES: ['daily', 'weekly', 'monthly', 'monthnth', 'yearly', 'yearnth'],
        RECURRENCE_INSTANCE: {1: 'first', 2: 'second', 3: 'third', 4: 'fourth', 5: 'last'},
        RECURRENCE_DAYOFWEEK: DAYOFWEEK,
        RECURRENCE_WEEKDAYS: DAYOFWEEK.slice(1, 6),
        RECURRENCE_WEEKENDS: [DAYOFWEEK[0], DAYOFWEEK[6]],
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

        /**
         * @inheritdoc
         */
        constructor: function EventRecurrenceModel(...args) {
            EventRecurrenceModel.__super__.constructor.apply(this, args);
        },

        isEmptyRecurrence: function() {
            return this.RECURRENCE_TYPES.indexOf(this.get('recurrenceType')) === -1;
        },
        /**
         * Compares data with model's attributes
         *
         * @param {object} values
         * @returns {Boolean}
         */
        isEqual: function(values) {
            values = _.mapObject(values, this._fieldCast);
            const attributes = _.mapObject(this.attributes, this._fieldCast);
            return _.isEqual(values, attributes);
        },

        _fieldCast: function(value, key) {
            if (key === 'dayOfWeek') {
                return _.sortBy(value);
            } else if (_.contains(['interval', 'instance', 'dayOfMonth', 'monthOfYear', 'occurences'], key)) {
                return Number(value) || null;
            } else {
                return value;
            }
        }
    });

    return EventRecurrenceModel;
});
