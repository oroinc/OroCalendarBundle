define(function(require) {
    'use strict';

    var EventRecurrenceView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var template = require('tpl!orocalendar/templates/calendar/event/recurrence/event-recurrence-template.html');
    var BaseView = require('oroui/js/app/views/base/view');

    EventRecurrenceView = BaseView.extend({
        RECURRENCE_REPEATS: {
            daily: ['daily'],
            weekly: ['weekly'],
            monthly: ['monthly', 'monthnth'],
            yearly: ['yearly', 'yearnth']
        },

        /** @type {string} defines name prefix for all form elements that are related to recurrence */
        inputNamePrefixes: '',

        template: template,

        events: {
            'change [data-name="recurrence-repeat"]': 'onRecurrentToggle',
            'change [data-name="recurrence-repeats"]': 'onRepeatsChange'
        },

        initialize: function(options) {
            _.extend(this, _.pick(options, 'inputNamePrefixes'));
            EventRecurrenceView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            var RECURRENCE_REPEATS = this.RECURRENCE_REPEATS;
            var data = EventRecurrenceView.__super__.getTemplateData.call(this);

            data.cid = this.cid;
            data.inputNamePrefixes = this.inputNamePrefixes;

            data.repeatsOptions = _.map(_.keys(this.RECURRENCE_REPEATS), function(item) {
                return {
                    value: item,
                    label: __('oro.calendar.event.recurrence.repeat.' + item),
                    selected: RECURRENCE_REPEATS[item].indexOf(data.recurrenceType) !== -1
                };
            });
            data.recurrenceTypeOptions = _.map(this.model.RECURRENCE_TYPES, function(item) {
                return {
                    value: item,
                    label: item,
                    selected: data.recurrenceType === item
                };
            });
            data.instanceOptions = _.map(this.model.RECURRENCE_INSTANCE, function(item, key) {
                return {
                    value: key,
                    label: __('oro.calendar.event.recurrence.instance.' + item),
                    selected: Number(data.instance) === Number(key)
                };
            });
            data.dayOfWeekOptions = _.map(this.model.RECURRENCE_DAYOFWEEK, function(item) {
                return {
                    value: item,
                    label: item,
                    selected: data.dayOfWeek.indexOf(item) !== -1
                };
            });

            return data;
        },

        findElement: function(shortName) {
            return this.$('[data-name="recurrence-' + shortName + '"]');
        },

        onRecurrentToggle: function(e) {
            this.findElement('settings').toggle(e.target.checked);
            this.$el.trigger('content:changed');
        },

        onRepeatsChange: function() {
            // @todo switch to proper Recurrence{Repeat}View
        }
    });

    return EventRecurrenceView;
});
