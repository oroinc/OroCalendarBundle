define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const template = require('tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence.html');
    const originValuesTemplate =
        require('tpl-loader!orocalendar/templates/calendar/event/recurrence/recurrence-origin-values.html');
    const BaseView = require('oroui/js/app/views/base/view');
    const RecurrenceEndsView = require('orocalendar/js/calendar/event/recurrence/recurrence-ends-view');
    const RecurrenceSummaryView = require('orocalendar/js/calendar/event/recurrence/recurrence-summary-view');
    const RecurrenceDailyView = require('orocalendar/js/calendar/event/recurrence/recurrence-daily-view');
    const RecurrenceWeeklyView = require('orocalendar/js/calendar/event/recurrence/recurrence-weekly-view');
    const RecurrenceMonthlyView = require('orocalendar/js/calendar/event/recurrence/recurrence-monthly-view');
    const RecurrenceYearlyView = require('orocalendar/js/calendar/event/recurrence/recurrence-yearly-view');

    const EventRecurrenceView = BaseView.extend({
        RECURRENCE_REPEATS: {
            daily: 'daily',
            weekly: 'weekly',
            monthly: 'monthly',
            monthnth: 'monthly',
            yearly: 'yearly',
            yearnth: 'yearly'
        },

        RECURRENCE_REPEAT_VIEWS: {
            daily: RecurrenceDailyView,
            weekly: RecurrenceWeeklyView,
            monthly: RecurrenceMonthlyView,
            yearly: RecurrenceYearlyView
        },

        /** @type {string} defines name prefix for all form elements that are related to recurrence */
        inputNamePrefixes: '',

        /** @type {Array<{label:string, name:string, messages:Array}>|null} errors passed initially over options */
        errors: null,

        _isCompletelyRendered: false,

        /** @type {object|null} initial attributes' values of recurrence model */
        _initModelData: null,

        template: template,

        events: {
            'change [data-name="recurrence-repeat"]': 'onRecurrenceToggle',
            'change [data-name="recurrence-repeats"]': 'onRepeatsChange'
        },

        listen: {
            'change model': 'onModelChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function EventRecurrenceView(options) {
            EventRecurrenceView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, 'inputNamePrefixes', 'errors'));
            this._initModelData = this.model.toJSON();
            EventRecurrenceView.__super__.initialize.call(this, options);
            if (this.model.isEmptyRecurrence()) {
                this.syncRecurrenceStart();
            }
        },

        delegateEvents: function(events) {
            EventRecurrenceView.__super__.delegateEvents.call(this, events);
            const $from = this.$el.closest('form');
            this.$eventStart = $from.find('[data-name="field__start"]');
            this.$eventStart.on('change' + this.eventNamespace(), this.onEventStartChange.bind(this));
            this.$eventEnd = $from.find('[data-name="field__end"]');
            this.$eventEnd.on('change' + this.eventNamespace(), this.onEventEndChange.bind(this));
            // listens events on parent element of form to make sure that form validation is passed
            this.$formParent = $from.parent();
            this.$formParent.on('change' + this.eventNamespace(), this.trigger.bind(this, 'formChanged'));
            this.$formParent.on('submit' + this.eventNamespace(), this.trigger.bind(this, 'formSubmit'));
            return this;
        },

        undelegateEvents: function() {
            EventRecurrenceView.__super__.undelegateEvents.call(this);
            if (this.$eventStart) {
                this.$eventStart.off(this.eventNamespace());
                delete this.$eventStart;
            }
            if (this.$eventEnd) {
                this.$eventEnd.off(this.eventNamespace());
                delete this.$eventEnd;
            }
            if (this.$formParent) {
                this.$formParent.off(this.eventNamespace());
                delete this.$formParent;
            }
            return this;
        },

        getTemplateData: function() {
            const data = EventRecurrenceView.__super__.getTemplateData.call(this);
            const repeatViewName = this.getRepeatViewName(data.recurrenceType);

            data.errors = this.errors;
            data.cid = this.cid;
            data.repeatsOptions = _.map(_.keys(this.RECURRENCE_REPEAT_VIEWS), function(item) {
                return {
                    value: item,
                    label: __('oro.calendar.event.recurrence.repeat.' + item),
                    selected: item === repeatViewName
                };
            });

            return data;
        },

        render: function() {
            EventRecurrenceView.__super__.render.call(this);

            if (!this.model.isEmptyRecurrence()) {
                delete this._isCompletelyRendered;
                this.renderSubviews();
            }

            this.renderOriginValues();

            this.$el.trigger('content:changed');

            return this;
        },

        renderSubviews: function() {
            const repeatViewName = this.getRepeatViewName(this.model.get('recurrenceType')) ||
                _.keys(this.RECURRENCE_REPEAT_VIEWS)[0];

            _.each(this.RECURRENCE_REPEAT_VIEWS, function(View, name) {
                const $el = this.findElement(name).hide();
                this.subview(name, new View({
                    enabled: repeatViewName === name,
                    autoRender: true,
                    el: $el,
                    model: this.model
                }));
            }, this);

            this.subview('ends', new RecurrenceEndsView({
                autoRender: true,
                el: this.findElement('ends'),
                model: this.model,
                minDatetime: this.$eventEnd.val()
            }));

            this.subview('summary', new RecurrenceSummaryView({
                autoRender: true,
                el: this.findElement('summary'),
                model: this.model
            }));

            this.switchRepeatView(repeatViewName);

            this._isCompletelyRendered = true;
        },

        getOriginValuesTemplateData: function() {
            const data = EventRecurrenceView.__super__.getTemplateData.call(this);

            data.inputNamePrefixes = this.inputNamePrefixes;
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

            this.$el.inputWidget('seekAndCreate');

            return data;
        },

        renderOriginValues: function() {
            let html;
            if (!this.model.isEmptyRecurrence() && this.findElement('repeat').is(':checked')) {
                html = originValuesTemplate(this.getOriginValuesTemplateData());
            } else {
                html = '<input name="' + this.inputNamePrefixes + '" value="" />';
            }
            this.findElement('origin-values').html(html);
            this.$el.inputWidget('seekAndCreate');
        },

        findElement: function(shortName) {
            return this.$('[data-name="recurrence-' + shortName + '"]');
        },

        onRecurrenceToggle: function(e) {
            const isRecurrenceActive = e.target.checked;
            if (!this._isCompletelyRendered) {
                this.renderSubviews();
            }

            this.findElement('settings').toggle(isRecurrenceActive);
            this.updateWarning();
            this.renderOriginValues();
            this.toggleEventReminders(!isRecurrenceActive);
            this.$el.trigger('content:changed');
        },

        onRepeatsChange: function(e) {
            const repeatViewName = e.target.value;
            this.switchRepeatView(repeatViewName);
        },

        onModelChange: function(model) {
            this.updateWarning();
            this.renderOriginValues();
            this.$el.trigger('content:changed');
        },

        onEventStartChange: function() {
            this.syncRecurrenceStart();
        },

        updateWarning: function() {
            let message = null;
            if (this._initModelData.recurrenceType) {
                if (!this.$('[data-name="recurrence-repeat"]').is(':checked')) {
                    message = __('oro.calendar.event.recurrence.warning.removing-all-series');
                } else if (!this.model.isEqual(this._initModelData)) {
                    message = __('oro.calendar.event.recurrence.warning.removing-all-exceptions');
                }
            }
            this.setWarning(message);
        },

        onEventEndChange: function() {
            const endsView = this.subview('ends');
            if (endsView) {
                endsView.syncRecurrenceEnd(this.$eventEnd.val());
            }
        },

        syncRecurrenceStart: function() {
            this.model.set('startTime', this.$eventStart.val());
        },

        switchRepeatView: function(repeatViewName) {
            _.each(_.keys(this.RECURRENCE_REPEAT_VIEWS), function(name) {
                const subview = this.subview(name);
                if (subview.isEnabled() && repeatViewName !== name) {
                    subview.disable();
                }
            }, this);

            this.subview(repeatViewName).enable();
        },

        getRepeatViewName: function(repeatType) {
            return this.RECURRENCE_REPEATS[repeatType];
        },

        setWarning: function(message) {
            if (message) {
                this.findElement('warning').html(message).show();
            } else {
                this.findElement('warning').hide();
            }
        },

        toggleEventReminders: function(isAvailable) {
            const $controlGroup = this.$el.closest('form').find('[name$="[reminders]"]').closest('.control-group');
            $controlGroup.find(':input[name*="[reminders]"]').each(function() {
                this.disabled = !isAvailable;
            });
            $controlGroup.toggle(isAvailable);
        }
    });

    return EventRecurrenceView;
});
