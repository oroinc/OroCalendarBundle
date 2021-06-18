define(function(require) {
    'use strict';

    const _ = require('underscore');
    const routing = require('routing');
    const moment = require('moment');
    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  orocalendar/js/calendar/event/model
     * @class   orocalendar.calendar.event.Model
     * @extends BaseModel
     */
    const CalendarEventModel = BaseModel.extend({
        route: 'oro_api_get_calendarevents',
        urlRoot: null,
        originalId: null, // original id received from a server

        defaults: {
            // original id is copied to originalId property and this attribute is replaced with calendarUid + originalId
            id: null,
            title: null,
            description: null,
            start: null,
            end: null,
            allDay: false,
            backgroundColor: null,
            reminders: {},
            parentEventId: null,
            invitationStatus: null,
            attendees: null,
            editable: false,
            editableInvitationStatus: false,
            removable: false,
            calendarAlias: null,
            calendar: null, // calendarId
            calendarUid: null, // calculated automatically, equals to calendarAlias + calendarId
            recurrence: null,
            recurrencePattern: null,
            recurringEventId: null,
            originalStart: null,
            isCancelled: null,
            updateExceptions: true,
            notifyAttendees: 'all'
        },

        /**
         * @inheritdoc
         */
        constructor: function CalendarEventModel(attrs, options) {
            CalendarEventModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function() {
            this.urlRoot = routing.generate(this.route);
            this._updateComputableAttributes();
            this.on('change:id change:calendarAlias change:calendar', this._updateComputableAttributes, this);
        },

        url: function() {
            const id = this.get(this.idAttribute);

            this.set(this.idAttribute, this.originalId, {silent: true});
            const url = CalendarEventModel.__super__.url.call(this);
            this.set(this.idAttribute, id, {silent: true});

            return url;
        },

        save: function(key, val, options) {
            let attrs;

            // Handle both `"key", value` and `{key: value}` -style arguments.
            if (key === null || key === undefined || typeof key === 'object') {
                attrs = key || {};
                options = val;
            } else {
                attrs = {};
                attrs[key] = val;
            }

            const auxiliaryAttrs = [
                'id',
                'editable',
                'editableInvitationStatus',
                'removable',
                'calendarUid',
                'parentEventId',
                'invitationStatus',
                'recurrencePattern',
                'durationEditable',
                'startEditable',
                'isOrganizer',
                'organizerUserId'
            ];

            if (this.get('recurringEventId') !== null) {
                auxiliaryAttrs.push('recurrence');
            }

            const modelData = _.extend(
                {id: this.originalId},
                _.omit(this.toJSON(), auxiliaryAttrs),
                attrs || {}
            );
            modelData.attendees = _.map(
                modelData.attendees,
                _.partial(_.pick, _, 'displayName', 'email', 'status', 'type')
            );
            options.contentType = 'application/json';
            options.data = JSON.stringify(modelData);

            CalendarEventModel.__super__.save.call(this, attrs, options);
        },

        _updateComputableAttributes: function() {
            const calendarAlias = this.get('calendarAlias');
            const calendarId = this.get('calendar');
            const calendarUid = calendarAlias && calendarId ? calendarAlias + '_' + calendarId : null;

            this.set('calendarUid', calendarUid);

            if (!this.originalId && !this.isNew() && calendarUid) {
                this.originalId = this.id;
                this.set('id', calendarUid + '_' + this.originalId, {silent: true});
            }

            if (this.get('recurrence') && !this.isNew()) {
                const start = new Date(this.get('start'));
                this.set('id', this.id + '_' + start.getTime(), {silent: true});
            }
        },

        validate: function(attrs) {
            const errors = [];

            if (moment(attrs.end).diff(attrs.start) < 0) {
                errors.push('oro.calendar.error_message.event_model.end_date_earlier_than_start');
            }

            return errors.length ? errors : null;
        },

        getInvitationStatus: function() {
            const invitationStatus = this.get('invitationStatus');

            return invitationStatus === '' ? null : invitationStatus;
        }
    });

    return CalendarEventModel;
});
