define(function(require) {
    'use strict';

    const CalendarComponent = require('orocalendar/js/app/components/calendar-component');
    const widgetManager = require('oroui/js/widget-manager');
    const moment = require('moment');

    const DashboardCalendarComponent = CalendarComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function DashboardCalendarComponent(options) {
            DashboardCalendarComponent.__super__.constructor.call(this, options);
        },

        renderCalendar: function() {
            DashboardCalendarComponent.__super__.renderCalendar.call(this);
            this.adoptWidgetActions();
        },

        adoptWidgetActions: function() {
            const component = this;
            function roundToHalfAnHour(moment) {
                const minutesToAdd = moment.minutes() < 30 ? 30 : 60;
                return moment.startOf('hour').add(minutesToAdd, 'm');
            }
            widgetManager.getWidgetInstance(this.options.widgetId, function(widget) {
                widget.getAction('new-event', 'adopted', function(newEventAction) {
                    newEventAction.on('click', function() {
                        component.calendar.showAddEventDialog({
                            start: roundToHalfAnHour(moment.utc()),
                            end: roundToHalfAnHour(moment.utc()).add(1, 'h')
                        });
                    });
                });
            });
        }
    });

    return DashboardCalendarComponent;
});
