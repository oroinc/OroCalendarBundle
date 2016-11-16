define(function(require) {
    'use strict';

    var RecurrenceYearlyView;
    var _ = require('underscore');
    var RecurrenceMonthlyView = require('orocalendar/js/calendar/event/recurrence/recurrence-monthly-view');

    RecurrenceYearlyView = RecurrenceMonthlyView.extend(/** @exports RecurrenceYearlyView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-yearly.html'),
        relatedFields: ['recurrenceType', 'interval', 'instance', 'dayOfWeek', 'dayOfMonth', 'monthOfYear'],

        getTemplateData: function() {
            var data = RecurrenceYearlyView.__super__.getTemplateData.apply(this, arguments);
            if (data.interval && data.interval >= 12) {
                data.interval /= 12;
            }
            data.monthsOptions = _.map(this.model.RECURRENCE_MONTHS, function(item, key) {
                return {
                    'value': key,
                    'text': item
                };
            });
            return data;
        },

        getValue: function() {
            var value = RecurrenceYearlyView.__super__.getValue.apply(this, arguments);
            if (value.interval) {
                value.interval *= 12;
            }
            return value;
        }
    });

    return RecurrenceYearlyView;
});
