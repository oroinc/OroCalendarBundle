import SwitchableRecurrenceSubview from 'orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview';
import template from 'tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence-daily.html';

const RecurrenceDailyView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceDailyView.prototype */{
    template,

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

export default RecurrenceDailyView;
