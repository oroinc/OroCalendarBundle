define(['oroui/js/app/views/base/view'
], function(BaseView) {
    'use strict';

    var ToggleCalendarView;
    /**
     * @export  orocalendar/js/calendar/menu/toggle-calendar
     * @class   orocalendar.calendar.menu.ToggleCalendar
     * @extends oroui/js/app/views/base/view
     */
    ToggleCalendarView = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function ToggleCalendarView() {
            ToggleCalendarView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.connectionsView = options.connectionsView;
        },

        execute: function(model) {
            this.connectionsView.toggleCalendar(model);
        }
    });

    return ToggleCalendarView;
});
