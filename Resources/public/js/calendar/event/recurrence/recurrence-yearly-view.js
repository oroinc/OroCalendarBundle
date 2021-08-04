define(function(require) {
    'use strict';

    const _ = require('underscore');
    const RecurrenceMonthlyView = require('orocalendar/js/calendar/event/recurrence/recurrence-monthly-view');

    const RecurrenceYearlyView = RecurrenceMonthlyView.extend(/** @exports RecurrenceYearlyView.prototype */{
        template: require('tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence-yearly.html'),

        relatedFields: ['recurrenceType', 'interval', 'instance', 'dayOfWeek', 'dayOfMonth', 'monthOfYear'],

        /**
         * @inheritdoc
         */
        constructor: function RecurrenceYearlyView(options) {
            RecurrenceYearlyView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = RecurrenceYearlyView.__super__.getTemplateData.call(this);
            if (data.interval && data.interval >= 12) {
                data.interval /= 12;
            }
            data.monthsOptions = _.map(this.model.RECURRENCE_MONTHS, function(item, key) {
                return {
                    value: key,
                    text: item,
                    selected: Number(key) === Number(data.monthOfYear)
                };
            });
            return data;
        },

        onModelChange: function(model) {
            const dayOfMonth = !this.model.get('instance') ? Number(this.model.get('dayOfMonth')) : null;
            const monthOfYear = Number(this.model.get('monthOfYear'));
            const daysInMonth = this._daysInMonth(monthOfYear);
            const $dayOfMonthField = this.$('[data-related-field="dayOfMonth"]');
            if (model.hasChanged('monthOfYear') || model.hasChanged('startTime')) {
                const dayValidationRules = $dayOfMonthField.data('validation');
                dayValidationRules.Number.max = daysInMonth;
                if ($dayOfMonthField.val()) {
                    $dayOfMonthField.trigger('blur');
                }
            }
            // the 29 of February was selected and it is a leap year
            if (monthOfYear === 2 && dayOfMonth === 29 && dayOfMonth === daysInMonth) {
                this.setFewerDaysWarning(dayOfMonth);
            } else {
                this.setFewerDaysWarning(false);
            }
        },

        _daysInMonth: function(month) {
            const fullYear = new Date(this.model.get('startTime')).getFullYear();
            return new Date(fullYear, month, 0).getDate();
        },

        getValue: function() {
            const value = RecurrenceYearlyView.__super__.getValue.call(this);
            if (value.interval) {
                value.interval *= 12;
            }
            return value;
        }
    });

    return RecurrenceYearlyView;
});
