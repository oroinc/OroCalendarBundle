define(function(require) {
    'use strict';

    var RecurrenceWeeklyView;
    var WeekDayPickerView = require('oroform/js/app/views/week-day-picker-view');
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    RecurrenceWeeklyView = AbstractRecurrenceSubview.extend(/** @exports RecurrenceWeeklyView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-weekly.html'),
        relatedFields: ['recurrenceType', 'interval', 'dayOfWeek'],
        render: function() {
            var data = this.getTemplateData();
            RecurrenceWeeklyView.__super__.render.apply(this, arguments);
            this.subview('week-day-picker', new WeekDayPickerView({
                autoRender: true,
                el: this.$('[data-name="recurrence-week-day-picker"]'),
                value: data.dayOfWeek,
                selectAttrs: {
                    'data-related-field': 'dayOfWeek',
                    'name': 'recurrence[dayOfWeek]'
                }
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
