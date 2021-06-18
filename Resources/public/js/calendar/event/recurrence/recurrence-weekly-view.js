define(function(require) {
    'use strict';

    const WeekDayPickerView = require('oroform/js/app/views/week-day-picker-view');
    const AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    const RecurrenceWeeklyView = AbstractRecurrenceSubview.extend(/** @exports RecurrenceWeeklyView.prototype */{
        template: require('tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence-weekly.html'),

        relatedFields: ['recurrenceType', 'interval', 'dayOfWeek'],

        /**
         * @inheritdoc
         */
        constructor: function RecurrenceWeeklyView(options) {
            RecurrenceWeeklyView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const data = this.getTemplateData();
            RecurrenceWeeklyView.__super__.render.call(this);
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
            const value = RecurrenceWeeklyView.__super__.getValue.call(this);
            value.dayOfWeek = this.subview('week-day-picker').getValue();
            return value;
        }
    });

    return RecurrenceWeeklyView;
});
