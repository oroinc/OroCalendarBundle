define(function(require) {
    'use strict';

    var RecurrenceEndsView;
    var __ = require('orotranslation/js/translator');
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    RecurrenceEndsView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceEndsView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-ends.html'),
        relatedFields: ['occurrences', 'endTime'],
        render: function() {
            RecurrenceEndsView.__super__.render.call(this);
            this.subview('date-time-picker-view', new DateTimePickerView({
                el: this.$('[data-related-field="endTime"]'),
                dateInputAttrs: {
                    placeholder: __('oro.form.choose_date'),
                    autocomplete: 'off',
                    'data-validation': JSON.stringify({Date: {}})
                },
                timeInputAttrs: {
                    placeholder: __('oro.form.choose_time'),
                    autocomplete: 'off',
                    'class': 'input-small timepicker-input',
                    'data-validation': JSON.stringify({Time: {}})
                },
                datePickerOptions: {
                    altFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-80:+1',
                    showButtonPanel: true
                }
            }));
            return this;
        },
        setInputsDisabled: function($inputs, isDisabled) {
            RecurrenceEndsView.__super__.setInputsDisabled.apply(this, arguments);
            var dateTimePickerView = this.subview('date-time-picker-view');
            if (dateTimePickerView && $inputs.index(dateTimePickerView.$el) !== -1) {
                dateTimePickerView.setDisabled(isDisabled);
            }
        }
    });

    return RecurrenceEndsView;
});
