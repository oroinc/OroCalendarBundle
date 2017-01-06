define(function(require) {
    'use strict';

    var RecurrenceSummaryView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var localeSettings = require('orolocale/js/locale-settings');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var BaseView = require('oroui/js/app/views/base/view');

    RecurrenceSummaryView = BaseView.extend(/** @exports RecurrenceSummaryView.prototype */{
        daysTranslationCache: {},
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-summary.html'),
        listen: {
            'change model': 'render'
        },

        getTemplateData: function() {
            var data = RecurrenceSummaryView.__super__.getTemplateData.apply(this, arguments);
            if (data.recurrenceType !== null) {
                var startTimeEventTZMoment = moment(data.startTime).tz(data.timeZone);
                var startTimeUserTZMoment = moment(data.startTime).tz(localeSettings.getTimeZone());
                if (startTimeEventTZMoment.format('dm') !== startTimeUserTZMoment.format('dm')) {
                    data.timezone_offset = startTimeEventTZMoment.format('Z');
                }
                if (Number(data.interval) > 0) {
                    data.count = data.interval;
                }
                if (data.endTime) {
                    data.formattedEndTime = datetimeFormatter.formatDate(data.endTime);
                }
                if (data.occurrences) {
                    data.occurrences = Number(data.occurrences) || '...';
                }
                if (data.dayOfWeek.length !== 0) {
                    data.day = _.map(data.dayOfWeek, _.bind(function(dayMnemonic) {
                        return this._translateDayOfWeek(dayMnemonic, 'wide');
                    }, this)).join(', ');
                }
                var methodName = 'process' + _.capitalize(data.recurrenceType) + 'RecurrenceData';
                if (methodName in this && _.isFunction(this[methodName])) {
                    data = this[methodName](data);
                }
                _.defaults(data, {
                    exclusion: null,
                    count: '...',
                    day: '...',
                    timezone_offset: null
                });
            }
            return data;
        },

        processWeeklyRecurrenceData: function(data) {
            var presetDaysValue = this._getPresetDaysValue(data.dayOfWeek);
            if (presetDaysValue === 'weekday' && Number(data.interval) === 1) {
                data.exclusion = 'weekday';
            }
            return data;
        },

        processMonthlyRecurrenceData: function(data) {
            if (Number(data.dayOfMonth) > 0) {
                data.day = data.dayOfMonth;
            }
            return data;
        },

        processMonthnthRecurrenceData: function(data) {
            var presetDaysValue = this._getPresetDaysValue(data.dayOfWeek);
            if (presetDaysValue !== null) {
                data.day = __('oro.calendar.event.recurrence.days.' + presetDaysValue);
            }
            data.instance = __('oro.calendar.event.recurrence.instance.' +
                this.model.RECURRENCE_INSTANCE[data.instance]).toLowerCase();
            return data;
        },

        processYearlyRecurrenceData: function(data) {
            if (Number(data.interval) >= 12) {
                data.count = Math.floor(data.interval / 12);
            }
            if (Number(data.dayOfMonth) > 0) {
                var dayInCurrentYear = new Date(data.startTime);
                dayInCurrentYear.setMonth(data.monthOfYear - 1);
                // adjust dateOfMonth to fit last day of month.
                var daysInMonth = Number(
                    new Date(new Date(data.startTime).getFullYear(), data.monthOfYear, 0).getDate()
                );
                var dayOfMonth = (Number(data.dayOfMonth) > daysInMonth) ? daysInMonth : Number(data.dayOfMonth);
                dayInCurrentYear.setDate(dayOfMonth);
                data.day = datetimeFormatter.formatDayDateTime(dayInCurrentYear);
            }
            return data;
        },

        processYearnthRecurrenceData: function(data) {
            data = this.processMonthnthRecurrenceData(data);
            var months = localeSettings.getCalendarMonthNames('wide');
            if (Number(data.interval) >= 12) {
                data.count = Math.floor(data.interval / 12);
            }
            data.month = months[data.monthOfYear];
            return data;
        },

        _getPresetDaysValue: function(dayOfWeek) {
            if (dayOfWeek.length === 7) {
                return 'day';
            } else if (_.haveEqualSet(dayOfWeek, this.model.RECURRENCE_WEEKDAYS)) {
                return 'weekday';
            } else if (_.haveEqualSet(dayOfWeek, this.model.RECURRENCE_WEEKENDS)) {
                return 'weekend';
            } else {
                return null;
            }
        },

        _translateDayOfWeek: function(dayOfWeek, width) {
            width = width || 'wide';
            if (!this.daysTranslationCache.hasOwnProperty(width)) {
                this.daysTranslationCache[width] = _.object(
                    this.model.RECURRENCE_DAYOFWEEK,
                    localeSettings.getCalendarDayOfWeekNames(width, true)
                );
            }
            return this.daysTranslationCache[width][dayOfWeek];
        }
    });

    return RecurrenceSummaryView;
});
