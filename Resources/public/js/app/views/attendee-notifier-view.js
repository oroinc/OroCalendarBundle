define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/app/views/base/view',
    'oroui/js/modal'
], function(_, __, BaseView, Modal) {
    'use strict';

    var AttendeeNotifierView = BaseView.extend({
        /** @property {Array} */
        exclusions: [
            'input[name="input_action"]',
            'input[name$="[backgroundColor]"]',
            'select[name*="[reminders]"]',
            'input[name*="[reminders]"]',
            'select[name*="[calendarUid]"]'
        ],

        /** @property {Object} */
        options: {
            separator: '-|-'
        },

        /**
         * @constructor
         */
        initialize: function(options) {
            this.options = _.defaults(_.pick(options || {}, _.keys(this.options)), this.options);
            var self = this;
            this.$form = this.$el.closest('form');
            this.$form.on('select2-data-loaded', function() {
                self.formInitialState = self.getFormState();
                self.attendeeInitialValues = self.getAttendeeValues();
            });
            this.formInitialState = this.getFormState();
            this.attendeeInitialValues = this.getAttendeeValues();
            this.isModalShown = false;
            this.notifyMessage = __('Notify about update message');
            this.$form.parent().on('submit.' + this.cid, _.bind(function(e) {
                if (!this.isModalShown && this.getFormState() !== this.formInitialState && this.hasAttendees()) {
                    if (!this.attendeeInitialValues.length) {
                        this.notifyMessage = __('Notify guests message');
                    }

                    this.getConfirmDialog().open();
                    this.isModalShown = true;
                    e.preventDefault();
                }
            }, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (!this.disposed) {
                if (this.$form) {
                    this.$form.parent().off('.' + this.cid);
                }
                if (this.confirmModal) {
                    this.confirmModal.dispose();
                    delete this.confirmModal;
                }
            }
            AttendeeNotifierView.__super__.dispose.call(this);
        },

        getAttendeeValues: function() {
            var value = this.$form.find('input[name*="[attendees]"]').val();
            return value.length > 0 ? value.split(this.options.separator) : [];
        },

        hasAttendees: function() {
            return this.$form.find('input[name*="[attendees]"]').val().indexOf('entityId') >= 0;
        },

        getConfirmDialog: function() {
            if (!this.confirmModal) {
                this.confirmModal = AttendeeNotifierView.createConfirmNotificationDialog(this.notifyMessage);
                this.listenTo(this.confirmModal, 'ok', _.bind(function() {
                    this.$form.find('input[name*="[notifyAttendees]"]').val('all');
                    this.$form.submit();
                    this.isModalShown = false;
                }, this));
                this.listenTo(this.confirmModal, 'cancel', _.bind(function() {
                    this.$form.find('input[name*="[notifyAttendees]"]').val('added_or_deleted');
                    this.$form.submit();
                    this.isModalShown = false;
                }, this));
                this.listenTo(this.confirmModal, 'close', _.bind(function() {
                    this.isModalShown = false;
                }, this));
            }
            return this.confirmModal;
        },

        getFormState: function() {
            return this.$form.find(':input:not(' + this.exclusions.join(', ') + ')').serialize();
        }
    }, {
        createConfirmNotificationDialog: function(notifyMessage) {
            notifyMessage = notifyMessage || __('Notify about update message');
            return new Modal({
                title: __('Notify guests title'),
                okText: __('Notify'),
                cancelText: __('Don\'t notify'),
                content: notifyMessage,
                className: 'modal modal-primary',
                handleClose: true
            });
        }
    });

    return AttendeeNotifierView;
});
