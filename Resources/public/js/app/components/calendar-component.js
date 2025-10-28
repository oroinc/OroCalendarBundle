import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';
import CalendarView from 'orocalendar/js/calendar-view';
import EventCollection from 'orocalendar/js/calendar/event/collection';
import ConnectionCollection from 'orocalendar/js/calendar/connection/collection';

/**
 * Creates calendar
 */
const CalendarComponent = BaseComponent.extend({

    /**
     * @type {orocalendar.js.calendar}
     */
    calendar: null,

    /**
     * @type {EventCollection}
     */
    eventCollection: null,

    /**
     * @type {ConnectionCollection}
     */
    connectionCollection: null,

    /**
     * @inheritdoc
     */
    constructor: function CalendarComponent(options) {
        CalendarComponent.__super__.constructor.call(this, options);
    },

    /**
     * @constructor
     * @param {Object} options
     */
    initialize: function(options) {
        this.options = options;
        if (!this.options.el) {
            this.options.el = this.options._sourceElement;
        }
        this.eventCollection = new EventCollection(JSON.parse(this.options.eventsItemsJson));
        this.connectionCollection = new ConnectionCollection(JSON.parse(this.options.connectionsItemsJson));
        delete this.options.eventsItemsJson;
        delete this.options.connectionsItemsJson;
        this.prepareOptions();
        this.renderCalendar();
    },
    prepareOptions: function() {
        const options = this.options;
        options.collection = this.eventCollection;
        options.scrollToCurrentTime = true;
        options.connectionsOptions.collection = this.connectionCollection;

        options.eventsOptions.header = {
            left: options.eventsOptions.leftHeader || '',
            center: options.eventsOptions.centerHeader || '',
            right: options.eventsOptions.rightHeader || ''
        };

        _.extend(options.eventsOptions, options.calendarOptions);

        delete options.calendarOptions;
        delete options.eventsOptions.centerHeader;
        delete options.eventsOptions.leftHeader;
        delete options.eventsOptions.rightHeader;
    },
    renderCalendar: function() {
        this.calendar = new CalendarView(this.options);
        this.calendar.render();
    }
});

export default CalendarComponent;
