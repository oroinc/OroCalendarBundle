define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'jquery.simplecolorpicker',
    'jquery.minicolors'
], function($, _, BaseView, __, messenger) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/menu/change-calendar-color
     * @class   orocalendar.calendar.menu.ChangeCalendarColor
     * @extends oroui/js/app/views/base/view
     */
    const ChangeCalendarColorView = BaseView.extend({
        /** @property */
        customColorPickerActionsTemplate: _.template('<div class="form-actions">' +
                '<button class="btn btn-primary pull-right" data-action="ok" type="button"><%- __("OK") %></button>' +
                '<button class="btn pull-right" data-action="cancel" type="button"><%- __("Cancel") %></button>' +
            '</div>'),

        events: {
            'change .color-picker': 'onChange',
            'click .custom-color-link': 'onOpen',
            'click button[data-action=ok]': 'onOk',
            'click button[data-action=cancel]': 'onCancel'
        },

        /**
         * @inheritdoc
         */
        constructor: function ChangeCalendarColorView(options) {
            ChangeCalendarColorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.colorManager = options.colorManager;
            this.connectionsView = options.connectionsView;
            this.$colorPicker = this.$el.find('.color-picker');
            this.$customColor = this.$el.find('.custom-color');
            this.$customColorParent = this.$customColor.parent();

            this.customColor = this.model.get('backgroundColor');
            if (_.indexOf(this.colorManager.colors, this.model.get('backgroundColor')) !== -1) {
                this.customColor = null;
            }

            this.initializeColorPicker();
            this.initializeCustomColorPicker();
        },

        initializeColorPicker: function() {
            const colors = _.map(this.colorManager.colors, function(value) {
                return {id: value, text: value};
            });

            this.$colorPicker.simplecolorpicker({theme: 'fontawesome', data: colors});
            if (!this.customColor) {
                this.$colorPicker.simplecolorpicker('selectColor', this.model.get('backgroundColor'));
            }
        },

        initializeCustomColorPicker: function() {
            this.$customColor.minicolors({
                control: 'wheel',
                letterCase: 'uppercase',
                defaultValue: this.model.get('backgroundColor'),
                change: (hex, opacity) => {
                    this.$customColor.css('color', this.colorManager.getContrastColor(hex));
                },
                show: () => {
                    const color = this.customColor || this.model.get('backgroundColor');
                    const $panel = this.$customColorParent.find('.minicolors-panel');
                    $panel.css('top', 0);
                    const h = $panel.outerHeight() + 39;
                    $panel.css('top', $(document).height() < $panel.offset().top + h ? -h : 0);
                    this.$colorPicker.simplecolorpicker('selectColor', null);
                    this.$customColor.minicolors('value', color);
                    this.$customColor.attr('data-selected', '');
                    this.$customColorParent.find('.minicolors-picker').show();
                }
            });

            this.$customColorParent.find('.minicolors-picker').hide();

            if (this.customColor) {
                this.$customColor.attr('data-selected', '');
                this.$customColor.css('color', this.colorManager.getContrastColor(this.model.get('backgroundColor')));
            } else {
                this.$customColorParent.hide();
            }

            // add buttons
            this.$customColorParent.find('.minicolors-panel').append(this.customColorPickerActionsTemplate({__: __}));
        },

        onChange: function(e) {
            this.changeColor(e.currentTarget.value);
        },

        onOpen: function(e) {
            e.stopPropagation();
            this.$customColor.minicolors('show');
            this.$customColorParent.show();
        },

        onOk: function() {
            this.$customColor.minicolors('hide');
            this.changeColor(this.$customColor.minicolors('value'));
        },

        onCancel: function() {
            this.$customColor.minicolors('hide');
            if (this.customColor) {
                this.$customColor.css({
                    'background-color': this.customColor,
                    'color': this.colorManager.getContrastColor(this.customColor)
                });
            } else {
                this.$customColor.removeAttr('data-selected');
                this.$colorPicker.simplecolorpicker('selectColor', this.model.get('backgroundColor'));
                this.$customColorParent.hide();
            }
        },

        changeColor: function(color) {
            if (this.connectionsView._initActionSyncObject()) {
                const savingMsg = messenger.notificationMessage('warning',
                    __('oro.calendar.flash_message.calendar_updating'));
                const $connection = this.connectionsView.findItem(this.model);
                const saveAttributes = {backgroundColor: color};
                if (!this.model.get('visible')) {
                    saveAttributes.visible = true;
                }
                this.connectionsView.setItemVisibility($connection, color);
                try {
                    this.model.save(saveAttributes, {
                        wait: true,
                        success: () => {
                            savingMsg.close();
                            messenger.notificationFlashMessage('success',
                                __('oro.calendar.flash_message.calendar_updated'), {namespace: 'calendar-ns'});
                            this.colorManager.setCalendarColors(this.model.get('calendarUid'), color);
                            this.connectionsView._actionSyncObject.resolve();
                        },
                        error: (model, response) => {
                            savingMsg.close();
                            this._showError(__('Sorry, the calendar update has failed.'), response.responseJSON || {});
                            this.connectionsView.setItemVisibility($connection,
                                this.model.get('visible') ? this.model.get('backgroundColor') : '');
                            this.connectionsView._actionSyncObject.reject();
                        }
                    });
                } catch (err) {
                    savingMsg.close();
                    this._showError(__('Sorry, an unexpected error has occurred.'), err);
                    this.connectionsView.setItemVisibility($connection,
                        this.model.get('visible') ? this.model.get('backgroundColor') : '');
                    this.connectionsView._actionSyncObject.reject();
                }
            } else {
                this._showError(__('Sorry, a synchronization error has occurred.'), '');
            }
        },

        _showError: function(message, err) {
            messenger.showErrorMessage(message, err);
        }
    });

    return ChangeCalendarColorView;
});
