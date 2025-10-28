import BaseView from 'oroui/js/app/views/base/view';

/**
 * @export  orocalendar/js/calendar/menu/toggle-calendar
 * @class   orocalendar.calendar.menu.ToggleCalendar
 * @extends oroui/js/app/views/base/view
 */
const ToggleCalendarView = BaseView.extend({
    /**
     * @inheritdoc
     */
    constructor: function ToggleCalendarView(options) {
        ToggleCalendarView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.connectionsView = options.connectionsView;
    },

    execute: function(model) {
        this.connectionsView.toggleCalendar(model);
    }
});

export default ToggleCalendarView;
