define(function(require) {
    'use strict';

    const SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    const RecurrenceDailyView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceDailyView.prototype */{
        template: require('tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence-daily.html'),

        relatedFields: ['recurrenceType', 'interval', 'dayOfWeek'],

        /**
         * @inheritdoc
         */
        constructor: function RecurrenceDailyView(options) {
            RecurrenceDailyView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = RecurrenceDailyView.__super__.getTemplateData.call(this);
            data.weekDays = this.model.RECURRENCE_DAYOFWEEK.slice(1, 6); // days except weekend
            return data;
        }
    });

    return RecurrenceDailyView;
});
