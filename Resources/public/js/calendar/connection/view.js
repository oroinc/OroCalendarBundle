define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Backbone = require('backbone');
    const __ = require('orotranslation/js/translator');
    const messenger = require('oroui/js/messenger');
    const loadModules = require('oroui/js/app/services/load-modules');
    const ConnectionCollection = require('orocalendar/js/calendar/connection/collection');
    const ConnectionModel = require('orocalendar/js/calendar/connection/model');

    /**
     * @export  orocalendar/js/calendar/connection/view
     * @class   orocalendar.calendar.connection.View
     * @extends Backbone.View
     */
    const CalendarConnectionView = Backbone.View.extend({
        /** @property {Object} */
        attrs: {
            calendarUid: 'data-calendar-uid',
            calendarAlias: 'data-calendar-alias',
            color: 'data-color',
            backgroundColor: 'data-bg-color',
            visible: 'data-visible'
        },

        /** @property {Object} */
        selectors: {
            container: '.calendars',
            itemContainer: '.connection-container',
            item: '.connection-item',
            lastItem: '.connection-item:last',
            findItemByCalendar: function(calendarUid) {
                return '.connection-item[data-calendar-uid="' + calendarUid + '"]';
            },
            newCalendarSelector: '#new_calendar',
            contextMenuTemplate: '#template-calendar-menu'
        },

        events: {
            'mouseover [data-role="connection-label"]': 'onOverCalendarLabel',
            'mouseout [data-role="connection-label"]': 'onOutCalendarLabel',
            'shown.bs.dropdown': 'onOpenDropdown',
            'hide.bs.dropdown': 'onHideDropdown'
        },

        /**
         * @inheritdoc
         */
        constructor: function CalendarConnectionView(options) {
            CalendarConnectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.collection = this.collection || new ConnectionCollection();
            this.collection.setCalendar(this.options.calendar);
            this.options.connectionsView = this;
            this.template = _.template($(this.options.itemTemplateSelector).html());
            this.contextMenuTemplate = _.template($(this.selectors.contextMenuTemplate).html());

            // render connected calendars
            this.collection.each(this.onModelAdded.bind(this));

            // subscribe to connection collection events
            this.listenTo(this.collection, 'add', this.onModelAdded);
            this.listenTo(this.collection, 'change', this.onModelChanged);
            this.listenTo(this.collection, 'destroy', this.onModelDeleted);

            // subscribe to connect new calendar event
            const container = this.$el.closest(this.selectors.container);
            container.find(this.selectors.newCalendarSelector).on('change', e => {
                const itemData = $(e.target).inputWidget('data');

                if (itemData) {
                    this.addModel(e.val, itemData.fullName, itemData.userId);
                    // clear autocomplete
                    $(e.target).inputWidget('val', '');
                }
            });
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(document).off('.' + this.cid);
            Backbone.View.prototype.dispose.call(this);
        },

        getCollection: function() {
            return this.collection;
        },

        findItem: function(model) {
            return this.$el.find(this.selectors.findItemByCalendar(model.get('calendarUid')));
        },

        onOpenDropdown: function(event) {
            $(event.relatedTarget).addClass('visible');
        },

        onHideDropdown: function(event) {
            $(event.relatedTarget).removeClass('visible');
        },

        onOverCalendarLabel: function(event) {
            $(event.currentTarget).find('[data-role="color-checkbox"]').trigger('focus');
        },

        onOutCalendarLabel: function(event) {
            $(event.currentTarget).find('[data-role="color-checkbox"]').trigger('blur');
        },

        setItemVisibility: function($item, backgroundColor) {
            const $visibilityCheckbox = $item.find('[data-role="color-checkbox"]');
            const colors = this.options.colorManager.getCalendarColors($item.attr(this.attrs.calendarUid));

            $item
                .attr(this.attrs.visible, backgroundColor.length ? 'true' : 'false')
                .attr(this.attrs.backgroundColor, backgroundColor || colors.backgroundColor)
                .attr(this.attrs.color, backgroundColor
                    ? this.options.colorManager.getContrastColor(backgroundColor)
                    : colors.color
                );

            $visibilityCheckbox
                .css({
                    '--checkbox-skin-color': backgroundColor || colors.backgroundColor
                })
                .find('[data-role="color-checkbox"]')
                .prop('checked', backgroundColor.length)
                .trigger('focus');
        },

        onModelAdded: function(model) {
            const viewModel = model.toJSON();
            // init text/background colors
            this.options.colorManager.applyColors(viewModel, () => {
                const $last = this.$el.find(this.selectors.lastItem);
                const calendarAlias = $last.attr(this.attrs.calendarAlias);
                return ['user', 'system', 'public'].indexOf(calendarAlias) !== -1
                    ? $last.attr(this.attrs.backgroundColor) : null;
            });
            this.options.colorManager.setCalendarColors(viewModel.calendarUid, viewModel.backgroundColor);
            model.set('backgroundColor', viewModel.backgroundColor);

            const $el = $(this.template(viewModel));
            // set 'data-' attributes
            _.each(this.attrs, (value, key) => {
                $el.attr(value, viewModel[key]);
            });
            // subscribe to toggle context menu
            $el.on('shown.bs.dropdown', function(e) {
                this.showContextMenu($(e.currentTarget), model);
            }.bind(this));

            this.$el.find(this.selectors.itemContainer).append($el);

            this._addVisibilityButtonEventListener(this.findItem(model), model);

            if (model.get('visible')) {
                this.trigger('connectionAdd', model);
            }
            this.$el.trigger('content:changed');
        },

        onModelChanged: function(model) {
            this.options.colorManager.setCalendarColors(model.get('calendarUid'), model.get('backgroundColor'));
            this.trigger('connectionChange', model);
        },

        onModelDeleted: function(model) {
            this.options.colorManager.removeCalendarColors(model.get('calendarUid'));
            this.findItem(model).remove();
            this.trigger('connectionRemove', model);
        },

        showCalendar: function(model) {
            this._showItem(model, true);
        },

        hideCalendar: function(model) {
            this._showItem(model, false);
        },

        toggleCalendar: function(model) {
            if (model.get('visible')) {
                this.hideCalendar(model);
            } else {
                this.showCalendar(model);
            }
        },

        showContextMenu: function($connection, model) {
            const $dropdownToggle = $connection.find('[data-toggle=dropdown]');
            const $container = $connection.find('[data-role="connection-menu-content"]');
            let $contextMenu = $(this.contextMenuTemplate(model.toJSON()));
            const modules = _.uniq($contextMenu.find('li[data-module]').map(function() {
                return $(this).data('module');
            }).get());

            let showLoadingTimeout;

            if (modules.length > 0) {
                // show loading message, if loading takes more than 100ms
                showLoadingTimeout = setTimeout(() => {
                    $container.html('<span class="loading-indicator"></span>');
                }, 100);

                // If dropdown will be closed before module are loaded just remove prepared context menu
                // to do nothing since used wont see result of menu rendering
                const onDropdownHidden = function() {
                    $contextMenu = null;
                };

                $connection.one('hide.bs.dropdown', onDropdownHidden);

                // load context menu
                loadModules(_.object(modules, modules), function(modules) {
                    clearTimeout(showLoadingTimeout);
                    $connection.off('hide.bs.dropdown', onDropdownHidden);

                    if ($contextMenu === null) {
                        return;
                    }

                    _.each(modules, (ModuleConstructor, moduleName) => {
                        $contextMenu.find('li[data-module="' + moduleName + '"]').each((index, el) => {
                            const action = new ModuleConstructor({
                                el: el,
                                model: model,
                                collection: this.options.collection,
                                connectionsView: this.options.connectionsView,
                                colorManager: this.options.colorManager
                            });
                            action.$el.one('click', '.action', () => {
                                if (this._initActionSyncObject()) {
                                    action.execute(model, this._actionSyncObject);
                                }
                            });
                        });
                    });

                    $container.html($contextMenu);

                    // Dropdown changed its size after content was inserted so it needs to correct its position
                    $dropdownToggle.dropdown('update');
                }, this);
            }
        },

        addModel: function(calendarId, calendarName, userId) {
            let savingMsg;
            let model;
            const calendarAlias = 'user';
            const calendarUid = calendarAlias + '_' + calendarId;
            const el = this.$el.find(this.selectors.findItemByCalendar(calendarUid));
            if (el.length > 0) {
                messenger.notificationFlashMessage('warning',
                    __('oro.calendar.flash_message.calendar_already_exists'), {namespace: 'calendar-ns'});
            } else {
                savingMsg = messenger.notificationMessage('warning', __('oro.calendar.flash_message.calendar_adding'));
                try {
                    model = new ConnectionModel({
                        targetCalendar: this.options.calendar,
                        calendarName: calendarName,
                        calendarAlias: calendarAlias,
                        calendar: calendarId,
                        calendarUid: calendarUid,
                        userId: userId
                    });
                    this.collection.create(model, {
                        wait: true,
                        success: () => {
                            savingMsg.close();
                            messenger.notificationFlashMessage('success',
                                __('oro.calendar.flash_message.calendar_added'), {namespace: 'calendar-ns'});
                        },
                        error: (collection, response) => {
                            savingMsg.close();
                            this.showAddError(response.responseJSON || {});
                        }
                    });
                } catch (err) {
                    savingMsg.close();
                    this.showMiscError(err);
                }
            }
        },

        showAddError: function(err) {
            this._showError(__('Sorry, the calendar addition has failed.'), err);
        },

        showUpdateError: function(err) {
            this._showError(__('Sorry, the calendar update has failed.'), err);
        },

        showMiscError: function(err) {
            this._showError(__('Sorry, an unexpected error has occurred.'), err);
        },

        _showError: function(message, err) {
            messenger.showErrorMessage(message, err);
        },

        _showItem: function(model, visible) {
            const savingMsg = messenger.notificationMessage('warning',
                __('oro.calendar.flash_message.calendar_updating'));
            const $connection = this.findItem(model);
            this._removeVisibilityButtonEventListener($connection, model);
            this.setItemVisibility($connection, visible ? model.get('backgroundColor') : '');
            try {
                model.save('visible', visible, {
                    wait: true,
                    success: () => {
                        savingMsg.close();
                        messenger.notificationFlashMessage('success',
                            __('oro.calendar.flash_message.calendar_updated'), {namespace: 'calendar-ns'});
                        this._addVisibilityButtonEventListener($connection, model);
                        if (this._actionSyncObject) {
                            this._actionSyncObject.resolve();
                        }
                    },
                    error: (model, response) => {
                        savingMsg.close();
                        this.showUpdateError(response.responseJSON || {});
                        this._addVisibilityButtonEventListener($connection, model);
                        this.setItemVisibility($connection, visible ? '' : model.get('backgroundColor'));
                        if (this._actionSyncObject) {
                            this._actionSyncObject.reject();
                        }
                    }
                });
            } catch (err) {
                savingMsg.close();
                this.showMiscError(err);
                this._addVisibilityButtonEventListener($connection, model);
                this.setItemVisibility($connection, visible ? '' : model.get('backgroundColor'));
                if (this._actionSyncObject) {
                    this._actionSyncObject.reject();
                }
            }
        },

        _addVisibilityButtonEventListener: function($connection, model) {
            $connection.find('[data-role="connection-label"]').on('click.' + this.cid, () => {
                if (this._initActionSyncObject()) {
                    this.toggleCalendar(model);
                }
            });
        },

        _removeVisibilityButtonEventListener: function($connection, model) {
            $connection.off('.' + this.cid);
        },

        _initActionSyncObject: function() {
            if (this._actionSyncObject) {
                return false;
            }
            this._actionSyncObject = $.Deferred();
            this._actionSyncObject.always(() => {
                delete this._actionSyncObject;
            });
            return true;
        }
    });

    return CalendarConnectionView;
});
