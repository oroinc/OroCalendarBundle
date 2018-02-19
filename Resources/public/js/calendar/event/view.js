define(function(require) {
    'use strict';

    var CalendarEventView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var mediator = require('oroui/js/mediator');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');
    var FormValidation = require('orocalendar/js/form-validation');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var fieldFormatter = require('oroform/js/formatter/field');
    var ActivityContextComponent = require('oroactivity/js/app/components/activity-context-activity-component');
    var orginizerTemplate = require('tpl!orocalendar/templates/calendar/event/organizer.html');

    CalendarEventView = BaseView.extend({
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
            CalendarEventView.__super__.remove.apply(this, arguments);
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
            var widgetOptions = this.options.widgetOptions || {};
            var defaultOptions = {
                title: this.model.isNew() ? __('Add New Event') : __('View Event'),
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: _.defaults(widgetOptions.dialogOptions || {}, {
                    modal: true,
                    resizable: false,
                    width: 475,
                    autoResize: true,
                    close: _.bind(this.disposePageComponents, this)
                }),
                submitHandler: _.bind(this.onEventFormSubmit, this)
            };

            if (this.options.widgetRoute) {
                defaultOptions.el = $('<div></div>');
                defaultOptions.url = routing.generate(this.options.widgetRoute, {id: this.model.originalId});
                defaultOptions.type = 'Calendar';
            } else {
                defaultOptions.el = this.model.isNew() ? this.getEventForm() : this.getEventView();
                defaultOptions.el.wrapInner('<div data-layout="separate" />');
                this.setElement(defaultOptions.el.find('>*:first'));
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
            this.eventDialog.render();

            // subscribe to 'delete event' event
            this.eventDialog.getAction('delete', 'adopted', _.bind(function(deleteAction) {
                deleteAction.on('click', _.bind(this._onClickDeleteDialogAction, this));
            }, this));
            // subscribe to 'switch to edit' event
            this.eventDialog.getAction('edit', 'adopted', _.bind(function(editAction) {
                editAction.on('click', _.bind(this._onClickEditDialogAction, this));
            }, this));

            // init loading mask control
            this.loadingMask = new LoadingMask({
                container: this.eventDialog.$el.closest('.ui-dialog')
            });

            this.showLoadingMask();
            this.initLayout({
                model: this.model,
                commonEventBus: this.options.commonEventBus
            }).always(_.bind(this._hideMask, this));

            return this;
        },

        _executePreAction: function(preactionName) {
            var promises = [];
            var $dialog = this.eventDialog.widget.closest('.ui-dialog');
            $dialog.addClass('invisible');
            this.options.commonEventBus.trigger('event:' + preactionName, this.model, promises, this.predefinedAttrs);
            return $.when.apply($, promises)
                .done(_.bind(function() {
                    var attrs = _.extend.apply(_, arguments);
                    this.setPredefinedAttrs(attrs);
                    $dialog.removeClass('invisible');
                }, this))
                .fail(_.bind(this.eventDialog.remove, this.eventDialog));
        },

        _onClickEditDialogAction: function(e) {
            this._executePreAction('beforeEdit')
                .then(_.bind(this._onEditDialogAction, this, e));
        },

        _onEditDialogAction: function(e) {
            var $content = this.getEventForm();
            $content.wrapInner('<div data-layout="separate" />');
            this.setElement($content.find('>*:first'));
            this.eventDialog.setTitle(__('Edit Event'));
            this.eventDialog.setContent($content);
            this.eventDialog.widget.dialog('option', 'width', 1000);
            // subscribe to 'delete event' event
            this.eventDialog.getAction('delete', 'adopted', _.bind(function(deleteAction) {
                deleteAction.on('click', _.bind(this._onClickDeleteDialogAction, this));
            }, this));
            this.showLoadingMask();
            this.initLayout({
                model: this.model,
                commonEventBus: this.options.commonEventBus
            }).always(_.bind(this._hideMask, this));
        },

        _onClickDeleteDialogAction: function(e) {
            this._executePreAction('beforeDelete')
                .then(_.bind(this._onDeleteDialogAction, this, e));
        },

        _onDeleteDialogAction: function(e) {
            var $el = $(e.currentTarget);
            var deleteUrl = $el.data('url');
            var deleteConfirmationMessage = $el.data('message');
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
                this._confirmDeleteAction(deleteConfirmationMessage, _.bind(this.deleteModel, this, deleteUrl));
                e.preventDefault();
            }
        },

        _confirmDeleteAction: function(message, callback) {
            if (this.model.get('recurringEventId')) {
                message += '<br/>' + __('Only this particular event will be deleted from the series.');
            }
            var confirm = new DeleteConfirmation({
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
            var previousData = this.model.toJSON();
            this.options.commonEventBus.trigger('eventForm:fetchData', this.model, formData, this.predefinedAttrs);
            this.model.set(formData);
            var errors;
            if (this.model.isValid()) {
                this.showSavingMask();
                try {
                    this.model.save(null, {
                        wait: true,
                        errorHandlerMessage: false,
                        success: _.bind(this._onSaveEventSuccess, this, previousData),
                        error: _.bind(this._handleResponseError, this)
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
                var options = {
                    wait: true,
                    errorHandlerMessage: false,
                    error: _.bind(this._handleResponseError, this)
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
            var self = this;
            form = $(form);

            self.buildForm(form, modelData);

            var inputs = form.find('[name]');
            var fieldNameRegex = /\[(\w+)\]/g;

            // show loading mask if child events users should be updated
            if (!_.isEmpty(modelData.attendees)) {
                this.eventDialog.once('renderComplete', function() {
                    self.showLoadingMask();
                });
            }

            _.each(inputs, function(input) {
                input = $(input);
                var name = input.attr('name');
                var matches = [];
                var match;

                while ((match = fieldNameRegex.exec(name)) !== null) {
                    matches.push(match[1]);
                }

                if (matches.length) {
                    var value = self.getValueByPath(modelData, matches);
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
            var self = this;
            form = $(form);
            _.each(modelData, function(value, key) {
                if (typeof value === 'object') {
                    var container = form.find('.' + key + '-collection');
                    if (container) {
                        var prototype = container.data('prototype');
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
            var connection = this.options.connections.findWhere({calendarUid: this.model.get('calendarUid')});
            var invitationUrls = [];
            _.each(this.options.invitationStatuses, function(status) {
                invitationUrls[status] = routing.generate('oro_calendar_event_' + status, {id: this.model.originalId});
            }, this);
            var $element = $(this.viewTemplate(_.extend(this.model.toJSON(), {
                organizerHTML: this._getOrganizerHTML(),
                formatter: fieldFormatter,
                connection: connection ? connection.toJSON() : null,
                invitationUrls: invitationUrls,
                originalId: this.model.originalId
            })));
            var $contextsSource = $element.find('.activity-context-activity');
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
            var templateData = this.getEventFormTemplateData();
            var form = this.fillForm(this.template(templateData), templateData);
            var calendarColors = this.options.colorManager.getCalendarColors(this.model.get('calendarUid'));

            form.find(this.selectors.backgroundColor)
                .data('page-component-options').emptyColor = calendarColors.backgroundColor;
            if (templateData.calendarAlias !== 'user') {
                this._showUserCalendarOnlyFields(form, false);
            }
            this._toggleCalendarUidByInvitedUsers(form);

            form.find(this.selectors.calendarUid).on('change', _.bind(function(e) {
                var $emptyColor = form.find('.empty-color');
                var $selector = $(e.currentTarget);
                var tagName = $selector.prop('tagName').toUpperCase();
                var calendarUid = tagName === 'SELECT' || $selector.is(':checked') ?
                    $selector.val() : this.model.get('calendarUid');
                var colors = this.options.colorManager.getCalendarColors(calendarUid);
                var newCalendar = this.parseCalendarUid(calendarUid);
                $emptyColor.css({'background-color': colors.backgroundColor, 'color': colors.color});
                if (newCalendar.calendarAlias === 'user') {
                    this._showUserCalendarOnlyFields(form);
                } else {
                    this._showUserCalendarOnlyFields(form, false);
                }
            }, this));
            form.find(this.selectors.attendees).on('change', _.bind(function(e) {
                this._toggleCalendarUidByInvitedUsers(form);
            }, this));

            // Adds calendar event activity contexts items to the form
            if (this.model.originalId) {
                var contexts = form.find(this.selectors.contexts);
                $.ajax({
                    url: routing.generate('oro_api_get_activity_context', {
                        activity: 'calendarevents', id: this.model.originalId
                    }),
                    type: 'GET',
                    success: _.bind(function(targets) {
                        var targetsStrArray = [];
                        targets.forEach(function(target) {
                            var targetData = {
                                entityClass: target.targetClassName.split('_').join('\\'),
                                entityId: target.targetId
                            };
                            targetsStrArray.push(JSON.stringify(targetData));
                        });
                        contexts.val(targetsStrArray.join(this.options.separator));
                        contexts.trigger('change');
                    }, this)
                });
            }

            return form;
        },

        getEventFormData: function() {
            var fieldNameFilterRegex = /^oro_calendar_event_form/;
            var fieldNameRegex = /\[(\w+)\]/g;
            var data = {};
            var $form = this.eventDialog.form;
            var formData = this.eventDialog.form.serializeArray().filter(function(item) {
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
                var existingItem = _.findWhere(result, {name: item.name});
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
                var matches = [];
                var match;
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
                var attendees = this.eventDialog.form.find('[name="oro_calendar_event_form[attendees]"]')
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
            var $calendarUid = form.find(this.selectors.calendarUid);
            if (!$calendarUid.length) {
                return;
            }
            if (this.model.get('recurringEventId') || this.predefinedAttrs.isException) {
                $calendarUid.attr('disabled', 'disabled');
                return;
            }

            var $attendeesSelect = form.find(this.selectors.attendees);
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
            var parent = obj;
            var i;

            for (i = 0; i < path.length - 1; i++) {
                if (parent[path[i]] === undefined) {
                    parent[path[i]] = {};
                }
                parent = parent[path[i]];
            }

            parent[path[path.length - 1]] = value;
        },

        getValueByPath: function(obj, path) {
            var current = obj;
            var i;

            for (i = 0; i < path.length; i++) {
                if (current[path[i]] === undefined || current[path[i]] === null) {
                    return undefined;
                }
                current = current[path[i]];
            }

            return current;
        },

        getEventFormTemplateData: function() {
            var isNew = this.model.isNew();
            var formData = this.model.toJSON();
            var templateType = '';
            var calendars = [];
            var ownCalendar = null;
            var isOwnCalendar = function(item) {
                return (item.get('calendarAlias') === 'user' && item.get('calendar') === item.get('targetCalendar'));
            };

            this.options.connections.each(function(item) {
                var calendar;
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
            var model = this.model;

            return orginizerTemplate({
                routing: routing,
                organizerUserId: model.get('organizerUserId'),
                organizerDisplayName: model.get('organizerDisplayName'),
                organizerEmail: model.get('organizerEmail')
            });
        }
    });

    return CalendarEventView;
});
