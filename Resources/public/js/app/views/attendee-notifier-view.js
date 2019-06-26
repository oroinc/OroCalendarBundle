define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var BaseView = require('oroui/js/app/views/base/view');
    var Modal = require('oroui/js/modal');
    var modalTemplate = require('tpl!oroui/templates/three-buttons-modal.html');

    var AttendeeNotifierView = BaseView.extend({
        /** @property {Array} */
        exclusions: [
            'input[name="input_action"]',
            'input[name$="[backgroundColor]"]',
            'select[name*="[reminders]"]',
            'input[name*="[reminders]"]',
            'select[name*="[calendarUid]"]',
            'input[name*="[notifyAttendees]"]'
        ],

        /** @property {Object} */
        options: {
            separator: '-|-'
        },

        /**
         * @inheritDoc
         */
        constructor: function AttendeeNotifierView() {
            AttendeeNotifierView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(_.pick(options || {}, _.keys(this.options)), this.options);
            this.$form = this.$el.closest('form');
            this.$form.on('select2-data-loaded', this.updateInitialState.bind(this));
            this.$notifyInput = this.$form.find('input[name*="[notifyAttendees]"]');
            this.updateInitialState();
            this.$form.parent().on('submit.' + this.cid, _.bind(function(e) {
                if (this.hasAttendees()) {
                    if (this.getFormState() !== this.formInitialState) {
                        this.$notifyInput.val('');
                    }

                    if (_.isEmpty(this.$notifyInput.val())) {
                        this.getConfirmDialog().open();

                        e.preventDefault();
                    }
                }
            }, this));
        },

        updateInitialState: function() {
            this.formInitialState = this.getFormState();
            this.attendeeInitialValues = this.getAttendeeValues();
            this.notifyMessage =
                __(this.attendeeInitialValues.length ? 'Notify about update message': 'Notify guests message');
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
                    this.$notifyInput.val('all');
                    this.updateInitialState();
                    this.$form.submit();
                }, this));
                this.listenTo(this.confirmModal, 'buttonClick', _.bind(function(buttonId) {
                    if (buttonId === 'secondary') {
                        this.$notifyInput.val('none');
                        this.updateInitialState();
                        this.$form.submit();
                    }
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
                secondaryText: __('Don\'t notify'),
                secondaryButtonClass: 'btn btn-primary',
                content: notifyMessage,
                className: 'modal modal-primary',
                template: modalTemplate,
                disposeOnHidden: false
            });
        }
    });

    return AttendeeNotifierView;
});
