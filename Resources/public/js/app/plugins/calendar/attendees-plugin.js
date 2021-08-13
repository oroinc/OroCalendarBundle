define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BasePlugin = require('oroui/js/app/plugins/base/plugin');
    const AttendeeNotifierView = require('orocalendar/js/app/views/attendee-notifier-view');

    const AttendeesPlugin = BasePlugin.extend({
        enable: function() {
            this.listenTo(this.main, 'event:added', this.onEventAdded);
            this.listenTo(this.main, 'event:changed', this.onEventChanged);
            this.listenTo(this.main, 'event:deleted', this.onEventDeleted);

            if (!this.options || this.options.enableAttendeesInvitations) {
                this.listenTo(this.main, 'event:beforeSave', this.onEventBeforeSave);
            }
            AttendeesPlugin.__super__.enable.call(this);
        },

        // no disable() function 'cause attached callbacks will be removed in parent disable method

        /**
         * Verifies if event is an attendee event
         *
         * @param eventModel
         * @returns {boolean}
         */
        hasParentEvent: function(eventModel) {
            let result = false;
            const parentEventId = eventModel.get('parentEventId');
            const alias = eventModel.get('calendarAlias');
            const self = this;
            if (parentEventId) {
                result = Boolean(this.main.getConnectionCollection().find(function(c) {
                    return c.get('calendarAlias') === alias &&
                        self.main.collection.get(c.get('calendarUid') + '_' + parentEventId);
                }, this));
            }
            return result;
        },

        /**
         * Verifies if event has a loaded attendees events
         *
         * @param parentEventModel
         * @returns {boolean}
         */
        hasLoadedAttendeeEvents: function(parentEventModel) {
            let result = false;
            let attendees = parentEventModel.get('attendees');
            attendees = _.isNull(attendees) ? [] : attendees;
            if (parentEventModel.hasChanged('attendees') && !_.isEmpty(parentEventModel.previous('attendees'))) {
                attendees = _.union(attendees, parentEventModel.previous('attendees'));
            }
            if (!_.isEmpty(attendees)) {
                result = this.main.getConnectionCollection().filter(function(connection) {
                    return _.findWhere(attendees, {userId: connection.get('userId')});
                }, this).length > 0;
            }
            return result;
        },

        /**
         * Returns linked attendees events
         *
         * @param parentEventModel
         * @returns {Array.<EventModel>}
         */
        findAttendeeEventModels: function(parentEventModel) {
            return this.main.collection.where({
                parentEventId: '' + parentEventModel.originalId
            });
        },

        /**
         * "event:added" callback
         *
         * @param eventModel
         */
        onEventAdded: function(eventModel) {
            eventModel.set('editable', eventModel.get('editable') && !this.hasParentEvent(eventModel), {silent: true});
            if (this.hasLoadedAttendeeEvents(eventModel)) {
                this.main.refreshView();
            }
        },

        /**
         * "event:changed" callback
         *
         * @param eventModel
         */
        onEventChanged: function(eventModel) {
            let attendeeEventModels;
            let i;
            let updatedAttrs;
            eventModel.set('editable', eventModel.get('editable') && !this.hasParentEvent(eventModel), {silent: true});
            if (this.hasLoadedAttendeeEvents(eventModel)) {
                if (eventModel.hasChanged('attendees')) {
                    this.listenToOnce(eventModel, 'sync', this.main.refreshView.bind(this.main));
                    return;
                }
                // update linked events
                attendeeEventModels = this.findAttendeeEventModels(eventModel);
                updatedAttrs = _.pick(eventModel.changedAttributes(),
                    ['start', 'end', 'allDay', 'title', 'description']);
                for (i = 0; i < attendeeEventModels.length; i++) {
                    // fill with updated attributes in parent
                    attendeeEventModels[i].set(updatedAttrs);
                }
            }
        },

        /**
         * "event:deleted" callback
         *
         * @param eventModel
         */
        onEventDeleted: function(eventModel) {
            let attendeeEventModels;
            let i;
            if (this.hasLoadedAttendeeEvents(eventModel)) {
                // remove guests
                attendeeEventModels = _.filter(this.findAttendeeEventModels(eventModel), function(attendeeEventModel) {
                    // in case there are multiple related guest models (sequence of recurring event)
                    // delete only with same start date
                    return attendeeEventModel.get('start') === eventModel.get('start');
                });
                for (i = 0; i < attendeeEventModels.length; i++) {
                    this.main.getCalendarElement().fullCalendar('removeEvents', attendeeEventModels[i].id);
                    this.main.collection.remove(attendeeEventModels[i]);
                    attendeeEventModels[i].dispose();
                }
            }
        },

        /**
         * "event:beforeSave" callback.
         *
         * @param eventModel
         * @param {Array.<$.promise>} promises script will wait execution of all promises before save
         * @param {object} attrs to be set on event model
         */
        onEventBeforeSave: function(eventModel, promises, attrs) {
            if (this.hasLoadedAttendeeEvents(eventModel)) {
                let cleanUp;
                const deferredConfirmation = $.Deferred();
                promises.push(deferredConfirmation);

                if (!this.modal) {
                    cleanUp = () => {
                        this.modal.dispose();
                        delete this.modal;
                    };

                    this.modal = AttendeeNotifierView.createConfirmNotificationDialog();
                    this.modal.on('ok', () => {
                        attrs.notifyAttendees = 'all';
                        deferredConfirmation.resolve();
                        _.defer(cleanUp);
                    });

                    this.modal.on('cancel', () => {
                        attrs.notifyAttendees = 'none';
                        deferredConfirmation.resolve();
                        _.defer(cleanUp);
                    });

                    this.modal.on('close', () => {
                        deferredConfirmation.reject();
                        _.defer(cleanUp);
                    });
                }

                this.modal.open();
            }
        }
    });

    return AttendeesPlugin;
});
