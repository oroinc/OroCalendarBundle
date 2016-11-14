define(function(require) {
    'use strict';

    var RecurrenceWeeklyView;
    var WeekDayPickerView = require('oroform/js/app/views/week-day-picker-view');
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    RecurrenceWeeklyView = AbstractRecurrenceSubview.extend(/** @exports RecurrenceWeeklyView.prototype */{
        autoRender: true,
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-weekly.html'),
        defaultData: {
            recurrenceType: 'daily',
            interval: 1,
            dayOfWeek: []
        },

        render: function () {
            RecurrenceWeeklyView.__super__.render.apply(this, arguments);
            this.subview('week-day-picker', new WeekDayPickerView({
                el: this.$('[data-name="week-day-picker"]'),
                value: this.dayOfWeek || this.defaultData.dayOfWeek,
                inputName: 'day-of-week-' + this.cid
            }));
            return this;
        },

        getValue: function() {
            var value = RecurrenceWeeklyView.__super__.getValue.apply(this, arguments);
            value.dayOfWeek = this.subview('week-day-picker').getValue();
            return value;
        }
    });

    return RecurrenceWeeklyView;
});
