define(function(require) {
    'use strict';

    var RecurrenceDailyView;
    var _ = require('underscore');
    var localeSettings = require('orolocale/js/locale-settings');
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    RecurrenceDailyView = AbstractRecurrenceSubview.extend(/** @exports RecurrenceDailyView.prototype */{
        autoRender: true,
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
        },

        getValue: function() {
            var value = RecurrenceDailyView.__super__.getValue.apply(this, arguments);
            var $activeInput = this.$('input[type=radio]:checked')
                .closest('[data-name="control-section"]').find('input[data-name="value"]');
            if ($activeInput.length) {
                value[$activeInput.data('field')] = $activeInput.val() || null;
            }
            return value;
        }
    });

    return RecurrenceDailyView;
});
