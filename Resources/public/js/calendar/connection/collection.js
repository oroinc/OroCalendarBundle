import Backbone from 'backbone';
import routing from 'routing';
import ConnectionModel from 'orocalendar/js/calendar/connection/model';

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

export default CalendarConnectionCollection;
