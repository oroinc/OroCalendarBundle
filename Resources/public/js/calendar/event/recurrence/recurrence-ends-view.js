define(function(require) {
    'use strict';

    var RecurrenceEndsView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var moment = require('moment');
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    RecurrenceEndsView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceEndsView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-ends.html'),
        relatedFields: ['occurrences', 'endTime'],
        /** @type {string|null} datetime in ISO format */
        minDatetime: null,

        initialize: function(options) {
            _.extend(this, _.pick(options, 'minDatetime'));
            RecurrenceEndsView.__super__.initialize.call(this, options);
        },

        render: function() {
            RecurrenceEndsView.__super__.render.call(this);

            this.setMinDatetime(this.minDatetime);
            this.subview('date-time-picker-view', new DateTimePickerView({
                el: this.$('[data-related-field="endTime"]'),
                dateInputAttrs: {
                    placeholder: __('oro.form.choose_date'),
                    autocomplete: 'off',
                    'class': 'datepicker-input',
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
                    yearRange: '-1:+80',
                    showButtonPanel: true,
                    minDate: new Date(this.minDatetime)
                }
            }));
            return this;
        },

        setMinDatetime: function(minDatetime) {
            this.$('[data-related-field="endTime"]')
                .attr('data-validation', JSON.stringify({}))
                .data('validation', {DateTime: {min: minDatetime}, NotBlank: {}});
            var dateTimePickerView = this.subview('date-time-picker-view');
            if (dateTimePickerView) {
                dateTimePickerView.setMinValue(new Date(minDatetime));
            }
        },

        syncRecurrenceEnd: function(eventEndValue) {
            var dateTimePickerView = this.subview('date-time-picker-view');
            var recurrentEndMoment = dateTimePickerView.getOriginalMoment();
            var eventEndMoment = moment.utc(eventEndValue, dateTimePickerView.backendFormat, true);
            if (eventEndMoment && recurrentEndMoment) {
                if (recurrentEndMoment.diff(eventEndMoment) < 0) {
                    dateTimePickerView.setValue(eventEndValue);
                }
            }
            this.setMinDatetime(eventEndValue);
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
