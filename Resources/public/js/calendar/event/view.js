define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const DialogWidget = require('oro/dialog-widget');
    const mediator = require('oroui/js/mediator');
    const LoadingMask = require('oroui/js/app/views/loading-mask-view');
    const FormValidation = require('orocalendar/js/form-validation');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');
    const fieldFormatter = require('oroform/js/formatter/field');
    const ActivityContextComponent = require('oroactivity/js/app/components/activity-context-activity-component');
    const orginizerTemplate = require('tpl-loader!orocalendar/templates/calendar/event/organizer.html');

    const CalendarEventView = BaseView.extend({
        /** @property {Object} */
        options: {
            calendar: null,
            commonEventBus: null,
            connections: null,
            colorManager: null,
            widgetRoute: null,
            widgetOptions: null,
            invitationStatuses: [],
            separator: '-|-'
        },

        /** @property {Object} */
        selectors: {
            loadingMaskContent: '.loading-content',
            backgroundColor: 'input[name$="[backgroundColor]"]',
            calendarUid: '[name*="calendarUid"]',
            attendees: 'input[name$="[attendees]"]',
            contexts: 'input[name$="[contexts]"]'
        },

        predefinedAttrs: {
            isException: false
        },

        /** @property {Array} */
        userCalendarOnlyFields: [
            {fieldName: 'reminders', emptyValue: {}, selector: '.reminders-collection'},
            {fieldName: 'attendees', emptyValue: '', selector: 'input[name$="[attendees]"]'}
        ],

        /**
         * @inheritdoc
         */
        constructor: function CalendarEventView(options) {
            CalendarEventView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(_.pick(options || {}, _.keys(this.options)), this.options);
            this.viewTemplate = _.template($(options.viewTemplateSelector).html());
            this.template = _.template($(options.formTemplateSelector).html());
            this.setPredefinedAttrs({});

            this.listenTo(this.model, 'sync', this.onModelSave);
            this.listenTo(this.model, 'destroy', this.onModelDelete);
            this.listenTo(mediator, 'widget_success:attendee_status:change', this.onAttendeeStatusChange);
        },

        remove: function() {
            this._hideMask();
            if (this.activityContext) {
                this.activityContext.dispose();
                delete this.activityContext;
            }
            CalendarEventView.__super__.remove.call(this);
        },

        /**
         * Updates object of predefined attributes and provides default values from its prototype
         *
         * @param {Object} attrs
         */
        setPredefinedAttrs: function(attrs) {
            this.predefinedAttrs = _.extend(Object.create(CalendarEventView.prototype.predefinedAttrs), attrs);
        },

        onModelSave: function() {
            this.trigger('addEvent', this.model);
            this.eventDialog.remove();
        },

        onModelDelete: function() {
            this.eventDialog.remove();
        },

        onAttendeeStatusChange: function() {
            this.eventDialog.remove();
        },

        render: function() {
            const widgetOptions = this.options.widgetOptions || {};
            const defaultOptions = {
                title: this.model.isNew() ? __('Add New Event') : __('View Event'),
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: _.defaults(widgetOptions.dialogOptions || {}, {
                    modal: true,
                    resizable: false,
                    width: 475,
                    autoResize: true,
                    close: this.disposePageComponents.bind(this)
                }),
                initLayoutOptions: {
                    model: this.model,
                    commonEventBus: this.options.commonEventBus
                },
                submitHandler: this.onEventFormSubmit.bind(this)
            };

            if (this.options.widgetRoute) {
                defaultOptions.el = $('<div></div>');
                defaultOptions.url = routing.generate(this.options.widgetRoute, {id: this.model.originalId});
                defaultOptions.type = 'Calendar';
            } else {
                defaultOptions.el = this.model.isNew() ? this.getEventForm() : this.getEventView();
                this.setElement(defaultOptions.el);
                if (this.model.isNew()) {
                    defaultOptions.dialogOptions.width = 1000;
                }
                defaultOptions.loadingMaskEnabled = false;
            }

            this.eventDialog = new DialogWidget(_.defaults(
                _.omit(widgetOptions, ['dialogOptions']),
                defaultOptions
            ));
            this.listenTo(this.eventDialog, 'widgetRemove', this.dispose);
            this.listenTo(this.eventDialog, 'widgetReady', this._hideMask);
            this.eventDialog.render();

            // subscribe to 'delete event' event
            this.eventDialog.getAction('delete', 'adopted', deleteAction => {
                deleteAction.on('click', this._onClickDeleteDialogAction.bind(this));
            });
            // subscribe to 'switch to edit' event
            this.eventDialog.getAction('edit', 'adopted', editAction => {
                editAction.on('click', this._onClickEditDialogAction.bind(this));
            });

            // init loading mask control
            this.loadingMask = new LoadingMask({
                container: this.eventDialog.$el.closest('.ui-dialog')
            });
            this.showLoadingMask();

            return this;
        },

        _executePreAction: function(preactionName) {
            const promises = [];
            const $dialog = this.eventDialog.widget.closest('.ui-dialog');
            $dialog.addClass('invisible');
            this.options.commonEventBus.trigger('event:' + preactionName, this.model, promises, this.predefinedAttrs);
            return $.when(...promises)
                .done(function(...args) {
                    const attrs = _.extend(...args);
                    this.setPredefinedAttrs(attrs);
                    $dialog.removeClass('invisible');
                }.bind(this))
                .fail(this.eventDialog.remove.bind(this.eventDialog));
        },

        _onClickEditDialogAction: function(e) {
            this._executePreAction('beforeEdit')
                .then(this._onEditDialogAction.bind(this, e));
        },

        _onEditDialogAction: function(e) {
            const $content = this.getEventForm();
            $content.wrapInner('<div data-layout="separate" />');
            this.setElement($content.find('>*:first'));
            this.eventDialog.setTitle(__('Edit Event'));
            this.eventDialog.setContent($content);
            this.eventDialog.widget.dialog('option', 'width', 1000);
            // subscribe to 'delete event' event
            this.eventDialog.getAction('delete', 'adopted', deleteAction => {
                deleteAction.on('click', this._onClickDeleteDialogAction.bind(this));
            });
            this.showLoadingMask();
            this.initLayout({
                model: this.model,
                commonEventBus: this.options.commonEventBus
            }).always(this._hideMask.bind(this));
        },

        _onClickDeleteDialogAction: function(e) {
            this._executePreAction('beforeDelete')
                .then(this._onDeleteDialogAction.bind(this, e));
        },

        _onDeleteDialogAction: function(e) {
            const $el = $(e.currentTarget);
            const deleteUrl = $el.data('url');
            const deleteConfirmationMessage = $el.data('message');
            if (this.model.get('recurrence')) {
                if (this.predefinedAttrs.isException) {
                    this.model.once('sync', function(model) {
                        model.set({id: null});
                        // restore back originalId from recurringEventId to allow destroying properly
                        // other related guest events, (backend sets new id for a deleted occurrence
                        // of recurring event, since it is represented as exceptionEvent with the flag isCancelled)
                        model.originalId = model.get('recurringEventId');
                        model.destroy();
                    }, this);
                    this._saveEventFromData({});
                } else {
                    this.setPredefinedAttrs({});
                    this.deleteModel(deleteUrl);
                }
            } else {
                this._confirmDeleteAction(deleteConfirmationMessage, this.deleteModel.bind(this, deleteUrl));
                e.preventDefault();
            }
        },

        _confirmDeleteAction: function(message, callback) {
            if (this.model.get('recurringEventId')) {
                message += '<br/>' + __('Only this particular event will be deleted from the series.');
            }
            const confirm = new DeleteConfirmation({
                content: message
            });
            confirm.on('ok', callback);
            confirm.open();
        },

        onEventFormSubmit: function() {
            // calendarUid value should be processed by form and value should be sent to backend
            this.eventDialog.$('form ' + this.selectors.calendarUid).removeAttr('disabled');
            this._saveEventFromData(this.getEventFormData());
        },

        _saveEventFromData: function(formData) {
            const previousData = this.model.toJSON();
            this.options.commonEventBus.trigger('eventForm:fetchData', this.model, formData, this.predefinedAttrs);
            this.model.set(formData);
            let errors;
            if (this.model.isValid()) {
                this.showSavingMask();
                try {
                    this.model.save(null, {
                        wait: true,
                        errorHandlerMessage: false,
                        success: this._onSaveEventSuccess.bind(this, previousData),
                        error: this._handleResponseError.bind(this)
                    });
                } catch (err) {
                    this.showError(err);
                }
            } else {
                errors = _.map(this.model.validationError, function(message) {
                    return __(message);
                });
                this.showError({errors: errors});
            }
        },

        _onSaveEventSuccess: function(previousData, model) {
            if (previousData.recurrence || model.get('recurrence')) {
                this.options.commonEventBus.trigger('toRefresh');
            }
        },

        deleteModel: function(deleteUrl) {
            this.showDeletingMask();
            try {
                const options = {
                    wait: true,
                    errorHandlerMessage: false,
                    error: this._handleResponseError.bind(this)
                };
                if (deleteUrl) {
                    options.url = routing.generate(deleteUrl, {id: this.model.originalId});
                } else {
                    options.url = this.model.url();
                }
                options.url += '?notifyAttendees=all';
                this.model.destroy(options);
            } catch (err) {
                this.showError(err);
            }
        },

        showSavingMask: function() {
            this._showMask(__('Saving...'));
        },

        showDeletingMask: function() {
            this._showMask(__('Deleting...'));
        },

        showLoadingMask: function() {
            this._showMask(__('Loading...'));
        },

        _showMask: function(message) {
            if (this.loadingMask) {
                this.loadingMask.show(message);
            }
        },

        _hideMask: function() {
            if (this.loadingMask) {
                this.loadingMask.hide();
            }
        },

        _handleResponseError: function(model, response) {
            if (response.status === 404) {
                mediator.execute(
                    'showMessage',
                    'error',
                    __('Calendar event doesn\'t exist. Please refresh page.')
                );
            }
            this.showError(response.responseJSON || {});
        },

        showError: function(err) {
            this._hideMask();
            if (this.eventDialog) {
                FormValidation.handleErrors(this.eventDialog.$el.parent(), err);
            }
        },

        fillForm: function(form, modelData) {
            const self = this;
            form = $(form);

            self.buildForm(form, modelData);

            const inputs = form.find('[name]');
            const fieldNameRegex = /\[(\w+)\]/g;

            // show loading mask if child events users should be updated
            if (!_.isEmpty(modelData.attendees)) {
                this.eventDialog.once('renderComplete', function() {
                    self.showLoadingMask();
                });
            }

            _.each(inputs, function(input) {
                input = $(input);
                const name = input.attr('name');
                const matches = [];
                let match;

                while ((match = fieldNameRegex.exec(name)) !== null) {
                    matches.push(match[1]);
                }

                if (matches.length) {
                    const value = self.getValueByPath(modelData, matches);
                    if (input.is(':checkbox')) {
                        if (value === false || value === true) {
                            input.prop('checked', value);
                        } else {
                            input.prop('checked', input.val() === value);
                        }
                    } else {
                        if (_.first(matches) === 'attendees') {
                            if (value && value.length) {
                                input.on('select2-data-loaded', function() {
                                    self._hideMask();
                                });
                                input.val(self.model.originalId);
                            }
                        } else {
                            input.val(value);
                        }
                    }
                    input.change();
                }
            }, this);

            return form;
        },

        buildForm: function(form, modelData) {
            const self = this;
            form = $(form);
            _.each(modelData, function(value, key) {
                if (typeof value === 'object') {
                    const container = form.find('.' + key + '-collection');
                    if (container) {
                        const prototype = container.data('prototype');
                        if (prototype) {
                            _.each(value, function(collectionValue, collectionKey) {
                                container.append(prototype.replace(/__name__/g, collectionKey));
                            });
                        }
                    }

                    self.buildForm(form, value);
                }
            });
        },

        getEventView: function() {
            // fetch calendar related connection
            const connection = this.options.connections.findWhere({calendarUid: this.model.get('calendarUid')});
            const invitationUrls = [];
            _.each(this.options.invitationStatuses, function(status) {
                invitationUrls[status] = routing.generate('oro_calendar_event_' + status, {id: this.model.originalId});
            }, this);
            const $element = $(this.viewTemplate(_.extend(this.model.toJSON(), {
                organizerHTML: this._getOrganizerHTML(),
                formatter: fieldFormatter,
                connection: connection ? connection.toJSON() : null,
                invitationUrls: invitationUrls,
                originalId: this.model.originalId
            })));
            const $contextsSource = $element.find('.activity-context-activity');
            this.activityContext = new ActivityContextComponent({
                _sourceElement: $contextsSource,
                checkTarget: false,
                activityClassAlias: 'calendarevents',
                entityId: this.model.originalId,
                editable: this.model.get('editable')
            });

            return $element;
        },

        getEventForm: function() {
            const templateData = this.getEventFormTemplateData();
            const form = this.fillForm(this.template(templateData), templateData);
            const calendarColors = this.options.colorManager.getCalendarColors(this.model.get('calendarUid'));

            form.find(this.selectors.backgroundColor)
                .data('page-component-options').emptyColor = calendarColors.backgroundColor;
            if (templateData.calendarAlias !== 'user') {
                this._showUserCalendarOnlyFields(form, false);
            }
            this._toggleCalendarUidByInvitedUsers(form);

            form.find(this.selectors.calendarUid).on('change', e => {
                const $emptyColor = form.find('.empty-color');
                const $selector = $(e.currentTarget);
                const tagName = $selector.prop('tagName').toUpperCase();
                const calendarUid = tagName === 'SELECT' || $selector.is(':checked')
                    ? $selector.val() : this.model.get('calendarUid');
                const colors = this.options.colorManager.getCalendarColors(calendarUid);
                const newCalendar = this.parseCalendarUid(calendarUid);
                $emptyColor.css({'background-color': colors.backgroundColor, 'color': colors.color});
                if (newCalendar.calendarAlias === 'user') {
                    this._showUserCalendarOnlyFields(form);
                } else {
                    this._showUserCalendarOnlyFields(form, false);
                }
            });
            form.find(this.selectors.attendees).on('change', e => {
                this._toggleCalendarUidByInvitedUsers(form);
            });

            // Adds calendar event activity contexts items to the form
            if (this.model.originalId) {
                const contexts = form.find(this.selectors.contexts);
                $.ajax({
                    url: routing.generate('oro_api_get_activity_context', {
                        activity: 'calendarevents', id: this.model.originalId
                    }),
                    type: 'GET',
                    success: targets => {
                        const targetsStrArray = [];
                        targets.forEach(function(target) {
                            const targetData = {
                                entityClass: target.targetClassName.split('_').join('\\'),
                                entityId: target.targetId
                            };
                            targetsStrArray.push(JSON.stringify(targetData));
                        });
                        contexts.val(targetsStrArray.join(this.options.separator));
                        contexts.trigger('change');
                    }
                });
            }

            return form;
        },

        getEventFormData: function() {
            const fieldNameFilterRegex = /^oro_calendar_event_form/;
            const fieldNameRegex = /\[(\w+)\]/g;
            const data = {};
            const $form = this.eventDialog.form;
            let formData = this.eventDialog.form.serializeArray().filter(function(item) {
                return fieldNameFilterRegex.test(item.name);
            });
            formData = formData.concat(this.eventDialog.form.find('input[name][type=checkbox]:not(:checked)')
                .filter(function(i, item) {
                    return fieldNameFilterRegex.test(item.name);
                })
                .map(function() {
                    return {name: this.name, value: false};
                }).get());
            // convert multiselect separate values into array of values
            formData = _.reduce(formData, function(result, item) {
                const existingItem = _.findWhere(result, {name: item.name});
                if (!existingItem && _.isArray($form.find('[name="' + item.name + '"]').val())) {
                    // convert first value of multiselect into array
                    item.value = [item.value];
                }
                if (existingItem && _.isArray(existingItem.value)) {
                    existingItem.value.push(item.value);
                } else {
                    result.push(item);
                }
                return result;
            }, []);
            _.each(formData, function(dataItem) {
                const matches = [];
                let match;
                while ((match = fieldNameRegex.exec(dataItem.name)) !== null) {
                    matches.push(match[1]);
                }

                if (matches.length) {
                    this.setValueByPath(data, dataItem.value, matches);
                }
            }, this);

            if (data.hasOwnProperty('calendarUid')) {
                if (data.calendarUid) {
                    _.extend(data, this.parseCalendarUid(data.calendarUid));
                    if (data.calendarAlias !== 'user') {
                        _.each(this.userCalendarOnlyFields, function(item) {
                            if (item.fieldName) {
                                data[item.fieldName] = item.emptyValue;
                            }
                        });
                    }
                }
                delete data.calendarUid;
            }

            if (data.hasOwnProperty('attendees')) {
                const attendees = this.eventDialog.form.find('[name="oro_calendar_event_form[attendees]"]')
                    .select2('data');
                data.attendees = _.map(attendees, function(attendee) {
                    return {
                        displayName: attendee.displayName,
                        email: attendee.email,
                        fullName: attendee.text,
                        status: attendee.status,
                        type: attendee.type,
                        userId: attendee.userId
                    };
                });
            }

            _.defaults(data, {
                reminders: {},
                recurrence: null
            });

            return data;
        },

        parseCalendarUid: function(calendarUid) {
            return {
                calendarAlias: calendarUid.substr(0, calendarUid.lastIndexOf('_')),
                calendar: parseInt(calendarUid.substr(calendarUid.lastIndexOf('_') + 1))
            };
        },

        _showUserCalendarOnlyFields: function(form, visible) {
            _.each(this.userCalendarOnlyFields, function(item) {
                if (item.selector) {
                    if (_.isUndefined(visible) || visible) {
                        form.find(item.selector).closest('.control-group').show();
                    } else {
                        form.find(item.selector).closest('.control-group').hide();
                    }
                }
            });
        },

        _toggleCalendarUidByInvitedUsers: function(form) {
            const $calendarUid = form.find(this.selectors.calendarUid);
            if (!$calendarUid.length) {
                return;
            }
            if (this.model.get('recurringEventId') || this.predefinedAttrs.isException) {
                $calendarUid.attr('disabled', 'disabled');
                return;
            }

            const $attendeesSelect = form.find(this.selectors.attendees);
            if ($attendeesSelect.val() && $attendeesSelect.select2('data').length > 0) {
                $calendarUid.attr('disabled', 'disabled');
                $calendarUid.parent().attr('title', __('The calendar cannot be changed because the event has guests'));
                // fix select2 dynamic change disabled
                if (!$calendarUid.parent().hasClass('disabled')) {
                    $calendarUid.parent().addClass('disabled');
                }
                if ($calendarUid.prop('tagName').toUpperCase() !== 'SELECT') {
                    $calendarUid.parent().find('label').addClass('disabled');
                }
            } else {
                $calendarUid.removeAttr('disabled');
                $calendarUid.parent().removeAttr('title');
                // fix select2 dynamic change disabled
                if ($calendarUid.parent().hasClass('disabled')) {
                    $calendarUid.parent().removeClass('disabled');
                }
                if ($calendarUid.prop('tagName').toUpperCase() !== 'SELECT') {
                    $calendarUid.parent().find('label').removeClass('disabled');
                }
            }
        },

        setValueByPath: function(obj, value, path) {
            let parent = obj;
            let i;

            for (i = 0; i < path.length - 1; i++) {
                if (parent[path[i]] === undefined) {
                    parent[path[i]] = {};
                }
                parent = parent[path[i]];
            }

            parent[path[path.length - 1]] = value;
        },

        getValueByPath: function(obj, path) {
            let current = obj;
            let i;

            for (i = 0; i < path.length; i++) {
                if (current[path[i]] === undefined || current[path[i]] === null) {
                    return undefined;
                }
                current = current[path[i]];
            }

            return current;
        },

        getEventFormTemplateData: function() {
            const isNew = this.model.isNew();
            const formData = this.model.toJSON();
            let templateType = '';
            const calendars = [];
            let ownCalendar = null;
            const isOwnCalendar = function(item) {
                return (item.get('calendarAlias') === 'user' && item.get('calendar') === item.get('targetCalendar'));
            };

            this.options.connections.each(function(item) {
                let calendar;
                if (item.get('canAddEvent')) {
                    calendar = {uid: item.get('calendarUid'), name: item.get('calendarName')};
                    if (!ownCalendar && isOwnCalendar(item)) {
                        ownCalendar = calendar;
                    } else {
                        calendars.push(calendar);
                    }
                }
            }, this);

            if (calendars.length) {
                if (isNew && calendars.length === 1) {
                    templateType = 'single';
                } else {
                    if (ownCalendar) {
                        calendars.unshift(ownCalendar);
                    }
                    templateType = 'multiple';
                }
            }

            _.extend(formData, {
                calendarUidTemplateType: templateType,
                calendars: calendars
            });

            this.options.commonEventBus.trigger('eventForm:setupData', this.model, formData, this.predefinedAttrs);

            return formData;
        },

        _getOrganizerHTML: function() {
            const model = this.model;

            return orginizerTemplate({
                routing: routing,
                organizerUserId: model.get('organizerUserId'),
                organizerDisplayName: _.escape(model.get('organizerDisplayName')),
                organizerEmail: _.escape(model.get('organizerEmail'))
            });
        }
    });

    return CalendarEventView;
});
