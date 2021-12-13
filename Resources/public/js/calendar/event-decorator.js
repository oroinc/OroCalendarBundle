define([
    'underscore',
    'orotranslation/js/translator'
], function(_, __) {
    'use strict';

    return {
        /** @property {Object} */
        templates: {
            reminderIcon: '<span class="reminder-status fa-bell-o" title="' + _.escape(__('Reminders')) + '"></span>',
            notRespondedIcon: '<span class="invitation-status fa-reply" ' +
                'title="' + _.escape(__('Not responded')) + '"></span>',
            tentativeIcon: '<span class="invitation-status fa-question-circle" ' +
                'title="' + _.escape(__('Tentatively accepted')) + '"></span>',
            acceptedIcon: '<span class="invitation-status fa-check" title="' + _.escape(__('Accepted')) + '"></span>'
        },

        decorate: function(eventModel, $el) {
            const $body = $el.find('.fc-content');
            let $timePlace = $el.find('.fc-time');
            const reminders = eventModel.get('reminders');
            const invitationStatus = eventModel.getInvitationStatus();
            // if $time is not displayed show related info into $body
            if (!$timePlace.length) {
                $timePlace = $body;
            }
            if (reminders && _.keys(reminders).length) {
                $el.prepend(this.templates.reminderIcon);
            } else {
                $el.find('.reminder-status').remove();
            }
            switch (invitationStatus) {
                case 'none':
                    $timePlace.prepend(this.templates.notRespondedIcon);
                    break;
                case 'accepted':
                    $timePlace.prepend(this.templates.acceptedIcon);
                    break;
                case 'tentative':
                    $timePlace.prepend(this.templates.tentativeIcon);
                    break;
                case 'declined':
                    $body.addClass('invitation-status-declined');
                    break;
                default:
                    $body.find('.invitation-status').remove();
                    $body.removeClass('invitation-status-declined');
                    eventModel._isInvitationIconAdded = false;
                    break;
            }
        }
    };
});
