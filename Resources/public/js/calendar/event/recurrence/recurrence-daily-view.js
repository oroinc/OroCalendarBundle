define(function(require) {
    'use strict';

    var RecurrenceDailyView;
    var SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    RecurrenceDailyView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceDailyView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-daily.html'),
        relatedFields: ['recurrenceType', 'interval', 'dayOfWeek'],
        getTemplateData: function() {
            var data = RecurrenceDailyView.__super__.getTemplateData.apply(this, arguments);
            data.weekDays = this.model.RECURRENCE_DAYOFWEEK.slice(1, 6); // days except weekend
            return data;
        }
    });

    return RecurrenceDailyView;
});
