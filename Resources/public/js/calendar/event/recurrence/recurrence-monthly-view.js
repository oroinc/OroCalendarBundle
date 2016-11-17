define(function(require) {
    'use strict';

    var RecurrenceMonthlyView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var localeSettings = require('orolocale/js/locale-settings');
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    RecurrenceMonthlyView = AbstractRecurrenceSubview.extend(/** @exports RecurrenceMonthlyView.prototype */{
        weekendDays: [],
        weekDays: [],
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-monthly.html'),
        relatedFields: ['recurrenceType', 'interval', 'instance', 'dayOfWeek', 'dayOfMonth'],
        events: {
            'change [data-related-field="instance"]': 'onInstanceChange'
        },

        initialize: function() {
            RecurrenceMonthlyView.__super__.initialize.apply(this, arguments);
            this.weekendDays = [this.model.RECURRENCE_DAYOFWEEK[0], this.model.RECURRENCE_DAYOFWEEK[6]];
            this.weekDays = _.difference(this.model.RECURRENCE_DAYOFWEEK, this.weekendDays);
        },

        getTemplateData: function() {
            var data = RecurrenceMonthlyView.__super__.getTemplateData.apply(this, arguments);
            data.repeatOnOptions = _.map(this.model.RECURRENCE_INSTANCE, function(item, key) {
                return {
                    'value': key,
                    'text': item
                };
            });
            var dayOfWeek = _.object(
                localeSettings.getSortedDayOfWeekNames('mnemonic'),
                localeSettings.getSortedDayOfWeekNames('wide')
            );
            data.dayOfWeekOptions =  _.map(dayOfWeek, function(text, key) {
                return {
                    value: key,
                    text: text
                };
            });
            data.groupWeekDayOptions =  [
                {
                    value: 'weekday',
                    text: 'weekday',
                    selected: _.haveEqualSet(this.weekDays, data.dayOfWeek)
                }, {
                    value: 'weekend-day',
                    text: 'weekend-day',
                    selected: _.haveEqualSet(this.weekendDays, data.dayOfWeek)
                }
            ];
            return data;
        },

        render: function() {
            RecurrenceMonthlyView.__super__.render.apply(this, arguments);
            this.updateControlBlocksState();
            return this;
        },

        onInstanceChange: function(e) {
            this.updateControlBlocksState();
        },

        updateControlBlocksState: function() {
            var repeatOnDayOfWeek = !this.$('[data-related-field="instance"]').val();
            this.$('[data-name="repeat-on-day-of-week"]').toggle(repeatOnDayOfWeek);
            this.$('[data-name="repeat-on-day-number"]').toggle(!repeatOnDayOfWeek);
        },

        dataInputs: function() {
            var $dataInputs = RecurrenceMonthlyView.__super__.dataInputs.apply(this, arguments);
            var hiddenControlBlock = this.$('[data-related-field="instance"]').val() ?
                this.$('[data-name="repeat-on-day-of-week"]') : this.$('[data-name="repeat-on-day-number"]');
            return $dataInputs.filter(function(index, element) {
                return !$.contains(hiddenControlBlock[0], element);
            });
        },

        updateModel: function() {
            RecurrenceMonthlyView.__super__.updateModel.call(this);
            var dayOfMonth = !this.model.get('instance') ? Number(this.model.get('dayOfMonth')) : null;
            if (dayOfMonth >= 29 && dayOfMonth <= 31) {
                this.$('[data-name="recurrence-warning"]')
                    .html(__('oro.calendar.event.recurrence.warning.day-' + dayOfMonth)).show();
            } else {
                this.$('[data-name="recurrence-warning"]').hide();
            }
        },

        getValue: function() {
            var value = RecurrenceMonthlyView.__super__.getValue.apply(this, arguments);
            if (value.dayOfWeek === 'weekday') {
                value.dayOfWeek = _.clone(this.weekDays);
            } else if (value.dayOfWeek === 'weekend-day') {
                value.dayOfWeek = _.clone(this.weekendDays);
            } else if (value.dayOfWeek && !_.isArray(value.dayOfWeek)) {
                value.dayOfWeek = [value.dayOfWeek];
            }
            return value;
        }
    });

    return RecurrenceMonthlyView;
});
