import CalendarComponent from 'orocalendar/js/app/components/calendar-component';
import widgetManager from 'oroui/js/widget-manager';
import moment from 'moment';

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

export default DashboardCalendarComponent;
