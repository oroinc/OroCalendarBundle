define([
    'backbone',
    'routing',
    'orocalendar/js/calendar/connection/model'
], function(Backbone, routing, ConnectionModel) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/connection/collection
     * @class   oro.calendar.connection.Collection
     * @extends Backbone.Collection
     */
    const CalendarConnectionCollection = Backbone.Collection.extend({
        route: 'oro_api_get_calendar_connections',

        url: null,

        model: ConnectionModel,

        /**
         * @inheritdoc
         */
        constructor: function CalendarConnectionCollection(...args) {
            CalendarConnectionCollection.__super__.constructor.apply(this, args);
        },

        /**
         * Sets a calendar this collection works with
         *
         * @param {int} calendarId
         */
        setCalendar: function(calendarId) {
            this.url = routing.generate(this.route, {id: calendarId});
        }
    });

    return CalendarConnectionCollection;
});
