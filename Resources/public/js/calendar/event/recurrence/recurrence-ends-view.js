define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const moment = require('moment');
    const DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    const SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    const RecurrenceEndsView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceEndsView.prototype */{
        template: require('tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence-ends.html'),

        relatedFields: ['occurrences', 'endTime'],

        /** @type {string|null} datetime in ISO format */
        minDatetime: null,

        /**
         * @inheritdoc
         */
        constructor: function RecurrenceEndsView(options) {
            RecurrenceEndsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
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
                    'placeholder': __('oro.form.choose_date'),
                    'class': 'datepicker-input',
                    'data-validation': JSON.stringify({Date: {}})
                },
                timeInputAttrs: {
                    'placeholder': __('oro.form.choose_time'),
                    'class': 'timepicker-input',
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
            const dateTimePickerView = this.subview('date-time-picker-view');
            if (dateTimePickerView) {
                dateTimePickerView.setMinValue(new Date(minDatetime));
            }
        },

        syncRecurrenceEnd: function(eventEndValue) {
            const dateTimePickerView = this.subview('date-time-picker-view');
            const recurrentEndMoment = dateTimePickerView.getOriginalMoment();
            const eventEndMoment = moment.utc(eventEndValue, dateTimePickerView.backendFormat, true);
            if (eventEndMoment && recurrentEndMoment) {
                if (recurrentEndMoment.diff(eventEndMoment) < 0) {
                    dateTimePickerView.setValue(eventEndValue);
                }
            }
            this.setMinDatetime(eventEndValue);
        },

        setInputsDisabled: function($inputs, isDisabled) {
            RecurrenceEndsView.__super__.setInputsDisabled.call(this, $inputs, isDisabled);
            const dateTimePickerView = this.subview('date-time-picker-view');
            if (dateTimePickerView && $inputs.index(dateTimePickerView.$el) !== -1) {
                dateTimePickerView.setDisabled(isDisabled);
            }
        }
    });

    return RecurrenceEndsView;
});
