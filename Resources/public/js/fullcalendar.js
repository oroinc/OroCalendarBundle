define(function(require) {
    'use strict';

    const $ = require('jquery');
    require('fullcalendar');

    $.fullCalendar.Calendar.prototype.normalizeEvent = function(event) {
        // Restore time in end day for all-day event, because end day must be inclusive
        // Time was stripped inside FullCalendar library by normalizeEventTimes method
        if (event.allDay && event.end) {
            event.end.time(event.endTime);
            event._end.time(event.endTime);
        }
    };
});
