define(function(require) {
    'use strict';

    var RecurrenceDailyView;
    var localeSettings = require('orolocale/js/locale-settings');
    var SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    RecurrenceDailyView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceDailyView.prototype */{
        template: require('tpl!orocalendar/templates/event/recurrence/recurrence-daily.html'),
        defaultData: {
            recurrenceType: 'daily',
            interval: 1,
            dayOfWeek: []
        },

        getTemplateData: function() {
            var data = RecurrenceDailyView.__super__.getTemplateData.apply(this, arguments);
            data.weekDays = localeSettings.getCalendarDayOfWeekNames('mnemonic', true).slice(1, 6);
            return data;
        }
    });

    return RecurrenceDailyView;
});
