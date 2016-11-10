define(function(require) {
    'use strict';

    var RecurrenceEndsView;
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    RecurrenceEndsView = AbstractRecurrenceSubview.extend(/** @exports RecurrenceEndsView.prototype */{
        autoRender: true,
        template: require('tpl!orocalendar/templates/event/recurrence/recurrence-ends.html'),
        defaultData:  {
            occurrences: null,
            endTime: null
        }
    });

    return RecurrenceEndsView;
});
