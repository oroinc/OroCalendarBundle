import {macros} from 'underscore';

macros('reminderTemplates', {
    /**
     * Renders contend for a calendar event reminder massage
     *
     * @param {Object} data
     * @param {string} data.subject
     * @param {string} data.expireAt
     * @param {string?} data.url
     */
    calendar_event_template: require('tpl-loader!orocalendar/templates/macros/calendar-event-reminder-template.html')
});
