define(function(require) {
    'use strict';

    var RecurrenceEndsView;
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    RecurrenceEndsView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceEndsView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-ends.html'),
        relatedFields: ['occurrences', 'endTime'],
        render: function() {
            RecurrenceEndsView.__super__.render.call(this);
            this.subview('date-time-picker-view', new DateTimePickerView({
                el: this.$('[data-related-field="endTime"]')
            }));
            this.updateControlSectionsState();
            return this;
        },
        setInputsDisabled: function($inputs, isDisabled) {
            RecurrenceEndsView.__super__.setInputsDisabled.apply(this, arguments);
            var dateTimePickerView = this.subview('date-time-picker-view');
            if (dateTimePickerView) {
                dateTimePickerView.setDisabled(isDisabled);
            }
        }
    });

    return RecurrenceEndsView;
});
