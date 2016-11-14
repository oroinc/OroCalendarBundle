define(function(require) {
    'use strict';

    var RecurrenceEndsView;
    var SwitchableRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/switchable-recurrence-subview');

    RecurrenceEndsView = SwitchableRecurrenceSubview.extend(/** @exports RecurrenceEndsView.prototype */{
        template: require('tpl!orocalendar/templates/calendar/event/recurrence/recurrence-ends.html'),
        defaultData:  {
            occurrences: null,
            endTime: null
        }
    });

    return RecurrenceEndsView;
});
