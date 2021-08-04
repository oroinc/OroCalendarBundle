define([
    'underscore',
    'oroui/js/app/views/base/view',
    'orotranslation/js/translator',
    'oroui/js/messenger'
], function(_, BaseView, __, messenger) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/remove-calendar
     * @class   orocalendar.calendar.menu.RemoveCalendar
     * @extends oroui/js/app/views/base/view
     */
    const RemoveCalendarView = BaseView.extend({
        /**
         * @inheritdoc
         */
        constructor: function RemoveCalendarView(options) {
            RemoveCalendarView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.connectionsView = options.connectionsView;
        },

        execute: function(model, actionSyncObject) {
            const removingMsg = messenger.notificationMessage('warning',
                __('oro.calendar.flash_message.calendar_removing'));
            const $connection = this.connectionsView.findItem(model);
            try {
                $connection.hide();
                model.destroy({
                    wait: true,
                    success: () => {
                        removingMsg.close();
                        messenger.notificationFlashMessage('success',
                            __('oro.calendar.flash_message.calendar_removed'), {namespace: 'calendar-ns'});
                        actionSyncObject.resolve();
                    },
                    error: (model, response) => {
                        removingMsg.close();
                        this._showError(__('Sorry, the calendar removal has failed.'), response.responseJSON || {});
                        $connection.show();
                        actionSyncObject.reject();
                    }
                });
            } catch (err) {
                removingMsg.close();
                this._showError(__('Sorry, an unexpected error has occurred.'), err);
                $connection.show();
                this.actionSyncObject.reject();
            }
        },

        _showError: function(message, err) {
            messenger.showErrorMessage(message, err);
        }
    });

    return RemoveCalendarView;
});
