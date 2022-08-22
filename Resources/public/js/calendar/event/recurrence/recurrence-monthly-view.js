define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const localeSettings = require('orolocale/js/locale-settings');
    const AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    const RecurrenceMonthlyView = AbstractRecurrenceSubview.extend(/** @exports RecurrenceMonthlyView.prototype */{
        template: require('tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence-monthly.html'),

        relatedFields: ['recurrenceType', 'interval', 'instance', 'dayOfWeek', 'dayOfMonth'],

        events: {
            'change [data-related-field="instance"]': 'onInstanceChange'
        },

        listen: {
            'change model': 'onModelChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function RecurrenceMonthlyView(options) {
            RecurrenceMonthlyView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = RecurrenceMonthlyView.__super__.getTemplateData.call(this);
            data.repeatOnOptions = _.map(this.model.RECURRENCE_INSTANCE, function(item, key) {
                return {
                    value: key,
                    text: item,
                    selected: Number(key) === Number(data.instance)
                };
            });
            const dayOfWeek = _.object(
                localeSettings.getSortedDayOfWeekNames('mnemonic'),
                localeSettings.getSortedDayOfWeekNames('wide')
            );
            data.dayOfWeekOptions = _.map(dayOfWeek, function(text, key) {
                return {
                    value: key,
                    text: text
                };
            });
            data.groupWeekDayOptions = [
                {
                    value: 'day',
                    text: 'day',
                    selected: _.haveEqualSet(this.model.RECURRENCE_DAYOFWEEK, data.dayOfWeek)
                }, {
                    value: 'weekday',
                    text: 'weekday',
                    selected: _.haveEqualSet(this.model.RECURRENCE_WEEKDAYS, data.dayOfWeek)
                }, {
                    value: 'weekend-day',
                    text: 'weekend-day',
                    selected: _.haveEqualSet(this.model.RECURRENCE_WEEKENDS, data.dayOfWeek)
                }
            ];
            return data;
        },

        render: function() {
            RecurrenceMonthlyView.__super__.render.call(this);
            this.updateControlBlocksState();
            return this;
        },

        setFewerDaysWarning: function(dayOfMonth) {
            if (dayOfMonth) {
                this.$('[data-name="recurrence-fewer-days-warning"]').html(
                    __('oro.calendar.event.recurrence.warning.some-months-have-fewer-days', {number: dayOfMonth})
                ).show();
            } else {
                this.$('[data-name="recurrence-fewer-days-warning"]').hide();
            }
        },

        onInstanceChange: function(e) {
            this.updateControlBlocksState();
        },

        updateControlBlocksState: function() {
            const repeatOnInstance = !this.$('[data-related-field="instance"]').val();

            this.$el.toggleClass('repeat-day-visible', repeatOnInstance);
            this.$el.toggleClass('repeat-instance-visible', !repeatOnInstance);
            this.$('[data-name="repeat-on-day"]').toggle(repeatOnInstance);
            this.$('[data-name="repeat-on-instance"]').toggle(!repeatOnInstance);
        },

        dataInputs: function() {
            const $dataInputs = RecurrenceMonthlyView.__super__.dataInputs.call(this);
            const hiddenControlBlock = this.$('[data-related-field="instance"]').val()
                ? this.$('[data-name="repeat-on-day"]') : this.$('[data-name="repeat-on-instance"]');
            return $dataInputs.filter(function(index, element) {
                return !$.contains(hiddenControlBlock[0], element);
            });
        },

        onModelChange: function() {
            const dayOfMonth = Number(this.model.get('dayOfMonth'));
            if (!this.model.get('instance') && dayOfMonth >= 29 && dayOfMonth <= 31) {
                this.setFewerDaysWarning(dayOfMonth);
            } else {
                this.setFewerDaysWarning(false);
            }
        },

        getValue: function() {
            const value = RecurrenceMonthlyView.__super__.getValue.call(this);
            if (value.dayOfWeek === 'weekday') {
                value.dayOfWeek = _.clone(this.model.RECURRENCE_WEEKDAYS);
            } else if (value.dayOfWeek === 'weekend-day') {
                value.dayOfWeek = _.clone(this.model.RECURRENCE_WEEKENDS);
            } else if (value.dayOfWeek === 'day') {
                value.dayOfWeek = _.clone(this.model.RECURRENCE_DAYOFWEEK);
            } else if (value.dayOfWeek && !_.isArray(value.dayOfWeek)) {
                value.dayOfWeek = [value.dayOfWeek];
            }
            return value;
        }
    });

    return RecurrenceMonthlyView;
});
