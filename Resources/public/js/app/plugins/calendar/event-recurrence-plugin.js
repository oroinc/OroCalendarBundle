define(function(require) {
    'use strict';

    var EventRecurrencePlugin;
    var moment = require('moment');
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var EventModel = require('orocalendar/js/calendar/event/model');
    var DialogWidget = require('oro/dialog-widget');
    var ActionTargetSelectView = require('orocalendar/js/calendar/event/action-target-select-view');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');

    EventRecurrencePlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main.commonEventBus, {
                'event:beforeSave': this.onEventBeforeSave,
                'event:beforeEdit': this.onEventBeforeEdit,
                'event:beforeDelete': this.onEventBeforeDelete,

                'eventForm:setupData': this.onEventFormSetupData,
                'eventForm:fetchData': this.onEventFormFetchData,

                'events:add': this.onEventUpdated,
                'events:destroy': this.onEventUpdated,
                'events:sync': this.onEventsSync
            });
            EventRecurrencePlugin.__super__.enable.call(this);
        },

        /**
         * Handles "event:beforeSave" event
         *
         * @param {EventModel} eventModel
         * @param {Array.<Promise>} promises script will wait execution of all promises before save
         * @param {Object} attrs to be set on event model
         */
        onEventBeforeSave: function(eventModel, promises, attrs) {
            var promise;
            var onSelectCallback;
            var options;
            if (eventModel.get('recurrence')) {
                options = {
                    actionType: 'edit',
                    dialogTitle: __('Edit Event')
                };

                onSelectCallback = _.bind(function(value) {
                    if (value === 'exception') {
                        _.extend(attrs, {
                            'id': null,
                            'recurringEventId': eventModel.originalId,
                            'originalStart': eventModel.get('start'),
                            'recurrence': null
                        });
                        eventModel.originalId = null;
                    } else {
                        var timeShift = moment(attrs.start).diff(eventModel.get('start'));
                        var duration = moment(attrs.end).diff(attrs.start);
                        var initialStart =
                            this._calculateInitialEvenStartEnd(eventModel.get('recurrence').startTime, attrs).start;
                        attrs.start = moment(initialStart).add(timeShift).tz('UTC').format();
                        attrs.end = moment(attrs.start).add(duration).tz('UTC').format();
                        attrs.recurrence = eventModel.get('recurrence');
                        delete attrs.recurrence.id;
                        attrs.recurrence.startTime = attrs.start;
                        if (attrs.recurrence.endTime) {
                            attrs.recurrence.endTime =
                                moment(attrs.recurrence.endTime).add(timeShift).tz('UTC').format();
                        }
                    }
                }, this);

                promise = this._selectActionTarget(onSelectCallback, options);
                promises.push(promise);
            }
        },

        /**
         * Handles "event:beforeEdit" event
         *
         * @param {EventModel} eventModel
         * @param {Array.<Promise>} promises script will wait execution of all promises before save
         * @param {Object} predefinedAttrs
         */
        onEventBeforeEdit: function(eventModel, promises, predefinedAttrs) {
            if (!eventModel.get('recurrence')) {
                return;
            }

            var options = {
                actionType: 'edit',
                dialogTitle: __('Edit Event')
            };
            var onSelectCallback = function(value) {
                var attrs;
                if (value === 'exception') {
                    attrs = {
                        isException: true,
                        originalStart: eventModel.get('start')
                    };
                }
                return attrs;
            };
            var promise = this._selectActionTarget(onSelectCallback, options);

            promises.push(promise);
        },

        /**
         * Handles "event:beforeDelete" event
         *
         * @param {EventModel} eventModel
         * @param {Array.<Promise>} promises script will wait execution of all promises before delete
         * @param {Object} predefinedAttrs
         */
        onEventBeforeDelete: function(eventModel, promises, predefinedAttrs) {
            if (!eventModel.get('recurrence')) {
                return;
            }

            var promise;

            // Restrict remove occurrence of recurring event in child calendar.
            // @todo This restriction should be removed in CRM-6758.
            var restrictOnlyThisEventOperation = eventModel.get('parentEventId') !== null &&
                eventModel.get('recurringEventId') === null;

            var options = {
                actionType: 'delete',
                dialogTitle: __('Delete Event'),
                restrictOnlyThisEventAction: restrictOnlyThisEventOperation
            };
            var onSelectCallback = function(value) {
                var attrs;
                if (value === 'exception' && !restrictOnlyThisEventOperation) {
                    attrs = {
                        isException: true,
                        originalStart: eventModel.get('start'),
                        isCancelled: true
                    };
                }
                return attrs;
            };

            if (predefinedAttrs.isException) {
                promise = $.Deferred().resolve(onSelectCallback('exception'));
            } else {
                promise = this._selectActionTarget(onSelectCallback, options);
            }

            promises.push(promise);
        },

        /**
         * Handles "eventForm:setupData" event
         *
         * @param {EventModel} eventModel
         * @param {Object} formData
         * @param {Object} predefinedAttrs
         */
        onEventFormSetupData: function(eventModel, formData, predefinedAttrs) {
            _.extend(formData, {
                editAsException: predefinedAttrs.isException
            });

            if (formData.id && formData.recurrence && !predefinedAttrs.isException) {
                _.extend(formData, this._calculateInitialEvenStartEnd(formData.recurrence.startTime, formData));
            }
        },

        /**
         * Handles "eventForm:fetchData" event
         *
         * @param {EventModel} eventModel
         * @param {Object} formData
         * @param {Object} predefinedAttrs
         */
        onEventFormFetchData: function(eventModel, formData, predefinedAttrs) {
            if (predefinedAttrs.isException) {
                _.extend(formData, {
                    'id': null,
                    'recurringEventId': eventModel.originalId,
                    'originalStart': predefinedAttrs.originalStart,
                    'recurrence': null,
                    'isCancelled': _.result(predefinedAttrs, 'isCancelled') || null
                });
                eventModel.originalId = null;
            }
        },

        /**
         * Handles "events:sync" event for an event model in events collection
         *
         * @param {EventModel|EventCollection} targetObject
         */
        onEventsSync: function(targetObject) {
            // if target of sync event is eventModel -- handle it as event model update event
            if (targetObject instanceof EventModel) {
                this.onEventUpdated(targetObject);
            }
        },

        /**
         * Handles "events:sync", "events:add" and "events:remove" events for an event model
         *
         * @param {EventModel} eventModel
         */
        onEventUpdated: function(eventModel) {
            if (eventModel.get('recurrence')) {
                this.main.commonEventBus.trigger('toRefresh');
            }
        },

        _selectActionTarget: function(onSelectCallback, options) {
            var deferredTargetSelection = $.Deferred();

            options = _.defaults(options, {restrictOnlyThisEventAction: false});

            var contentView = new ActionTargetSelectView({
                autoRender: true,
                actionType: options.actionType,
                // @todo This option should be removed in CRM-6758.
                restrictOnlyThisEventAction: options.restrictOnlyThisEventAction
            });

            var eventDialog = new DialogWidget({
                autoRender: true,
                el: contentView.$el.wrap('<div class="widget-content"/>').parent(),
                title: options.dialogTitle,
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    modal: true,
                    resizable: false,
                    width: 375,
                    // minHeight: 100,
                    autoResize: true,
                    close: function() {
                        deferredTargetSelection.reject();
                    }
                }
            });

            eventDialog.subview('content', contentView);

            eventDialog.getAction('apply', 'adopted', function(applyAction) {
                applyAction.on('click', function() {
                    var value = contentView.getValue();
                    deferredTargetSelection.resolve(onSelectCallback(value));
                    eventDialog.remove();
                });
            });

            return deferredTargetSelection.promise();
        },

        /**
         * Calculates start and end time of initial event on a base of duration and recurrence start of some occurrence
         *
         * @param {string} recurrenceStart datetime in ISO format of recurrence start
         * @param {Object} eventAttrs event attributes
         * @return {{start: string, end: string}}
         * @protected
         */
        _calculateInitialEvenStartEnd: function(recurrenceStart, eventAttrs) {
            var duration = moment(eventAttrs.end).diff(eventAttrs.start);
            return {
                start: recurrenceStart,
                end: moment(recurrenceStart).add(duration).tz('UTC').format()
            };
        }
    });

    return EventRecurrencePlugin;
});
