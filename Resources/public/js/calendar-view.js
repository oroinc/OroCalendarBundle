define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const moment = require('moment');
    const Backbone = require('backbone');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const messenger = require('oroui/js/messenger');
    const mediator = require('oroui/js/mediator');
    const LoadingMask = require('oroui/js/app/views/loading-mask-view');
    const BaseView = require('oroui/js/app/views/base/view');
    const EventCollection = require('orocalendar/js/calendar/event/collection');
    const EventModel = require('orocalendar/js/calendar/event/model');
    const EventView = require('orocalendar/js/calendar/event/view');
    const ConnectionView = require('orocalendar/js/calendar/connection/view');
    const eventDecorator = require('orocalendar/js/calendar/event-decorator');
    const ColorManager = require('orocalendar/js/calendar/color-manager');
    const colorUtil = require('oroui/js/tools/color-util');
    const dateTimeFormatter = require('orolocale/js/formatter/datetime');
    const localeSettings = require('orolocale/js/locale-settings');
    const PluginManager = require('oroui/js/app/plugins/plugin-manager');
    const AttendeesPlugin = require('orocalendar/js/app/plugins/calendar/attendees-plugin');
    const EventRecurrencePlugin = require('orocalendar/js/app/plugins/calendar/event-recurrence-plugin');
    const persistentStorage = require('oroui/js/persistent-storage');
    require('orocalendar/js/fullcalendar');
    const Modal = require('oroui/js/modal');

    require('fullcalendar');

    const CalendarView = BaseView.extend({
        MOMENT_BACKEND_FORMAT: dateTimeFormatter.getBackendDateTimeFormat(),

        /** @property */
        eventsTemplate: _.template(
            '<div>' +
                '<div class="calendar-container">' +
                    '<div class="calendar"></div>' +
                '</div>' +
            '</div>'
        ),

        events: {
            'click [data-role="show-connections-modal"]': 'onConnectionsButtonClick'
        },

        /** @property {Object} */
        selectors: {
            calendar: '.calendar',
            loadingMask: '.loading-mask',
            loadingMaskContent: '.loading-content'
        },

        /** @property {Object} */
        options: {
            timezone: localeSettings.getTimeZone(),
            eventsOptions: {
                defaultView: 'agendaWeek',
                allDayText: __('oro.calendar.control.all_day'),
                buttonText: {
                    today: __('oro.calendar.control.today'),
                    month: __('oro.calendar.control.month'),
                    week: __('oro.calendar.control.week'),
                    day: __('oro.calendar.control.day')
                },
                editable: true,
                removable: true,
                collection: null,
                fixedWeekCount: false, // http://fullcalendar.io/docs/display/fixedWeekCount/
                itemViewTemplateSelector: null,
                itemFormTemplateSelector: null,
                itemFormDeleteButtonSelector: null,
                calendar: null,
                subordinate: true,
                defaultTimedEventDuration: moment.duration('00:30:00'),
                defaultAllDayEventDuration: moment.duration('23:59:00'),
                header: {
                    ignoreTimezone: false,
                    allDayDefault: false
                },
                firstDay: localeSettings.getCalendarFirstDayOfWeek() - 1,
                monthNames: localeSettings.getCalendarMonthNames('wide', true),
                monthNamesShort: localeSettings.getCalendarMonthNames('abbreviated', true),
                dayNames: localeSettings.getCalendarDayOfWeekNames('wide', true),
                dayNamesShort: localeSettings.getCalendarDayOfWeekNames('abbreviated', true),
                recoverView: true,
                eventOrder: ['title', 'calendarUid'],
                enableAttendeesInvitations: true
            },
            connectionsOptions: {
                collection: null,
                containerTemplateSelector: null
            },
            colorManagerOptions: {
                colors: null
            },
            invitationStatuses: []
        },

        /**
         * this property is used to prevent loading of events from a server when the calendar object is created
         * @property {bool}
         */
        enableEventLoading: false,

        fullCalendar: null,

        eventView: null,

        loadingMask: null,

        colorManager: null,

        childDialogOfModal: null,

        /**
         * This property can be used to prevent unnecessary reloading of calendar events.
         * key = calendarUid
         * @property
         */
        eventsLoaded: {},

        listen: {
            'layout:reposition mediator': 'onWindowResize',
            'widget_success:attendee_status:change mediator': 'onAttendeeStatusChange',
            'widget_dialog:open mediator': 'onWidgetDialogOpen',
            'widget_dialog:close mediator': 'onWidgetDialogClose'
        },

        /**
         * One of 'fullscreen' | 'scroll' | 'default'
         * @property
         */
        layout: undefined,

        /**
         * @inheritdoc
         */
        constructor: function CalendarView(options) {
            CalendarView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (!options) {
                options = {};
            }
            if (options.eventsOptions) {
                _.defaults(options.eventsOptions, this.options.eventsOptions);
            }
            this.options = _.defaults(options || {}, this.options);
            // init event collection
            this.collection = this.collection || new EventCollection();
            this.collection.setCalendar(this.options.calendar);
            this.collection.subordinate = this.options.eventsOptions.subordinate;

            // set options for new events
            this.options.newEventEditable = this.options.eventsOptions.editable;
            this.options.newEventRemovable = this.options.eventsOptions.removable;

            if (this.options.eventsOptions.recoverView) {
                // try to retrieve the last view for this calendar
                const viewKey = this.getStorageKey('defaultView');
                const dateKey = this.getStorageKey('defaultDate');

                const defaultView = persistentStorage.getItem(viewKey);
                let defaultDate = persistentStorage.getItem(dateKey);

                if (defaultView) {
                    this.options.eventsOptions.defaultView = defaultView;
                }

                if (defaultDate && !isNaN(defaultDate)) {
                    defaultDate = moment.unix(defaultDate);
                    this.options.eventsOptions.defaultDate = defaultDate;
                    /**
                     * @TODO This is hotfix. Should be fixed in CRM-6061
                     */
                    this.enableEventLoading = (defaultDate.format('M') !== moment().format('M'));
                }
            }

            // subscribe to event collection events
            this.listenTo(this.collection, 'add', this.onEventAdded);
            this.listenTo(this.collection, 'change', this.onEventChanged);
            this.listenTo(this.collection, 'destroy', this.onEventDeleted);

            // to refresh calendar only once when it is invoked repeatedly
            this.refreshView = _.throttle(this.refreshView.bind(this), 10, {trailing: false});

            // create common event bus and subscribe to its events
            this.commonEventBus = Object.create(_.extend({}, Backbone.Events));
            this.listenTo(this.commonEventBus, 'toRefresh', _.debounce(this.refreshView, 0));
            this.listenTo(this, 'all', function(...args) {
                // translate all CalendarView events to commonEvenBus
                this.commonEventBus.trigger(...args);
            });
            this.listenTo(this.collection, 'all', function(name, ...args) {
                // translate all EventCollection events to commonEvenBus
                this.commonEventBus.trigger('events:' + name, ...args);
            });

            this.colorManager = new ColorManager(this.options.colorManagerOptions);

            this.pluginManager = new PluginManager(this);
            this.pluginManager.create(
                AttendeesPlugin,
                {enableAttendeesInvitations: options.eventsOptions.enableAttendeesInvitations}
            );
            this.pluginManager.enable(AttendeesPlugin);
            this.pluginManager.enable(EventRecurrencePlugin);
        },

        onWindowResize: function() {
            // fullCalendar might be not rendered yet
            if (this.getCalendarElement().data('fullCalendar')) {
                this.setTimeline();
                this.updateLayout();
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.layout === 'fullscreen') {
                // fullscreen layout has side effects, need to clean up
                this.setLayout('default');
            }

            this.pluginManager.dispose();
            clearInterval(this.timelineUpdateIntervalId);

            if (this.getCalendarElement().data('fullCalendar')) {
                this.getCalendarElement().fullCalendar('destroy');
            }

            this.childDialogOfModal = null;

            CalendarView.__super__.dispose.call(this);
        },

        getEventView: function(eventModel) {
            if (!this.eventView) {
                const connectionModel = this.getConnectionCollection().findWhere(
                    {calendarUid: eventModel.get('calendarUid')}
                );
                const options = connectionModel.get('options') || {};
                // create a view for event details
                this.eventView = new EventView(_.extend({}, options, {
                    model: eventModel,
                    commonEventBus: this.commonEventBus,
                    calendar: this.options.calendar,
                    connections: this.getConnectionCollection(),
                    viewTemplateSelector: this.options.eventsOptions.itemViewTemplateSelector,
                    formTemplateSelector: this.options.eventsOptions.itemFormTemplateSelector,
                    colorManager: this.colorManager,
                    invitationStatuses: this.options.invitationStatuses
                }));
                // subscribe to event view collection events
                this.listenTo(this.eventView, 'addEvent', this.handleEventViewAdd);
                this.listenTo(this.eventView, 'dispose', this.handleEventViewRemove);
            }
            return this.eventView;
        },

        handleEventViewRemove: function() {
            delete this.eventView;
        },

        /**
         * Init and get a loading mask control
         *
         * @returns {Element}
         */
        getLoadingMask: function() {
            if (!this.loadingMask) {
                this.loadingMask = new LoadingMask({
                    container: this.getCalendarElement()
                });
            }
            return this.loadingMask;
        },

        getCollection: function() {
            return this.collection;
        },

        getConnectionCollection: function() {
            return this.options.connectionsOptions.collection;
        },

        getCalendarElement: function() {
            if (!this.fullCalendar) {
                this.fullCalendar = this.$el.find(this.selectors.calendar);
            }
            return this.fullCalendar;
        },

        handleEventViewAdd: function(eventModel) {
            this.collection.add(eventModel);
        },

        addEventToCalendar: function(eventModel) {
            const fcEvent = this.createViewModel(eventModel);
            this.getCalendarElement().fullCalendar('renderEvent', fcEvent);
        },

        getCalendarEvents: function(calendarUid) {
            return this.getCalendarElement().fullCalendar('clientEvents', function(fcEvent) {
                return fcEvent.calendarUid === calendarUid;
            });
        },

        onEventAdded: function(eventModel) {
            const connectionModel = this.getConnectionCollection()
                .findWhere({calendarUid: eventModel.get('calendarUid')});

            if (!connectionModel) {
                return;
            }

            eventModel.set('editable', connectionModel.get('canEditEvent'), {silent: true});
            eventModel.set('removable', connectionModel.get('canDeleteEvent'), {silent: true});

            // trigger before view update
            this.trigger('event:added', eventModel);

            this.addEventToCalendar(eventModel);

            // make sure that a calendar is visible when a new event is added to it
            if (!connectionModel.get('visible')) {
                this.subview('connectionsView').showCalendar(connectionModel);
            }
        },

        onEventChanged: function(eventModel) {
            const connectionModel = this.getConnectionCollection()
                .findWhere({calendarUid: eventModel.get('calendarUid')});
            const calendarElement = this.getCalendarElement();

            eventModel.set('editable', connectionModel.get('canEditEvent'));
            eventModel.set('removable', connectionModel.get('canDeleteEvent'), {silent: true});

            if (eventModel.hasChanged('id')) {
                // if id value was deleted -- remove the event from calendar
                if (eventModel.previous('id') !== null) {
                    this.getCalendarElement().fullCalendar('removeEvents', eventModel.previous('id'));
                }

                // if new id value was assigned -- add the event to calendar
                if (eventModel.get('id') !== null) {
                    this.addEventToCalendar(eventModel);
                }
                return;
            }

            // find and update fullCalendar event model
            const fcEvent = calendarElement.fullCalendar('clientEvents', eventModel.id)[0];
            _.extend(fcEvent, this.createViewModel(eventModel));

            // trigger before view update
            this.trigger('event:changed', eventModel);

            // notify fullCalendar about update
            // NOTE: cannot update single event due to fullcalendar bug
            //       please check that after updating fullcalendar
            //       calendarElement.fullCalendar('updateEvent', fcEvent);
            calendarElement.fullCalendar('rerenderEvents');
        },

        onEventDeleted: function(eventModel) {
            if (eventModel.has('id')) {
                this.getCalendarElement().fullCalendar('removeEvents', eventModel.get('id'));
            }
            this.trigger('event:deleted', eventModel);
        },

        onAttendeeStatusChange: function() {
            this.refreshView();
        },

        onConnectionAdded: function() {
            this.refreshView();
        },

        onConnectionChanged: function(connectionModel) {
            if (connectionModel.reloadEventsRequest !== null) {
                if (connectionModel.reloadEventsRequest === true) {
                    this.updateEvents();
                }
                connectionModel.reloadEventsRequest = null;
                return;
            }

            const changes = connectionModel.changedAttributes();
            const calendarUid = connectionModel.get('calendarUid');
            if (changes.visible && !this.eventsLoaded[calendarUid]) {
                this.updateEvents();
            } else {
                this.updateEventsWithoutReload();
            }
        },

        onConnectionDeleted: function() {
            this.refreshView();
        },

        refreshView: function() {
            this.updateEvents();
            this.updateLayout();
        },

        /**
         * @param attrs object with properties to set on model before dialog creation
         *              dates must be in utc
         */
        showAddEventDialog: function(attrs) {
            let eventModel;

            // need to be able to accept native moments here
            // convert arguments
            if (!attrs.start._fullCalendar) {
                attrs.start = $.fullCalendar.moment(attrs.start.clone().utc().format());
            }
            if (attrs.end && !attrs.end._fullCalendar) {
                attrs.end = $.fullCalendar.moment(attrs.end.clone().utc().format());
            }
            if (!this.eventView) {
                try {
                    attrs.start = attrs.start.clone().utc().format(this.MOMENT_BACKEND_FORMAT);
                    attrs.end = attrs.end.clone().utc().format(this.MOMENT_BACKEND_FORMAT);
                    _.extend(attrs, {
                        calendarAlias: 'user',
                        calendar: this.options.calendar,
                        editable: this.options.newEventEditable,
                        removable: this.options.newEventRemovable
                    });
                    eventModel = new EventModel(attrs);
                    this.getEventView(eventModel).render();
                } catch (err) {
                    this.showMiscError(err);
                }
            }
        },

        onFcSelect: function(start, end) {
            const attrs = {
                allDay: start.time().as('ms') === 0 && end.time().as('ms') === 0,
                start: start.clone().tz(this.options.timezone, true),
                end: end.clone().tz(this.options.timezone, true)
            };

            // As end day is inclusive now in case of all-day event, end day must be the same as start day
            if (attrs.allDay) {
                attrs.end = attrs.end.clone().subtract(1, 'days');
            }

            this.showAddEventDialog(attrs);
        },

        onFcEventClick: function(fcEvent) {
            if (!this.eventView) {
                try {
                    const eventModel = this.collection.get(fcEvent.id);
                    this.getEventView(eventModel).render();
                } catch (err) {
                    this.showMiscError(err);
                }
            }
        },

        onFcEventResize: function(fcEvent, newDuration, undo) {
            this.saveFcEvent(fcEvent, undo);
        },

        onFcEventDragStart: function(fcEvent) {
            fcEvent._beforeDragState = {
                allDay: fcEvent.allDay,
                start: fcEvent.start.clone(),
                end: fcEvent.end ? fcEvent.end.clone() : null
            };
        },

        onFcEventDrop: function(fcEvent, dateDiff, undo, jsEvent) {
            let realDuration;
            const currentView = this.getCalendarElement().fullCalendar('getView');
            const oldState = fcEvent._beforeDragState;
            let isDroppedOnDayGrid =
                    fcEvent.start.time().as('ms') === 0 &&
                        (fcEvent.end === null || fcEvent.endTime !== null);

            // when on week view all-day event is dropped at 12AM to hour view
            // previous condition gives false positive result
            if (fcEvent.end === null && isDroppedOnDayGrid === true && fcEvent.start.time().as('ms') === 0) {
                isDroppedOnDayGrid = !$(jsEvent.target).parents('.fc-time-grid-event').length;
            }

            fcEvent.allDay = (currentView.name === 'month') ? oldState.allDay : isDroppedOnDayGrid;
            if (isDroppedOnDayGrid) {
                if (oldState.allDay) {
                    if (fcEvent.end === null && oldState.end === null) {
                        realDuration = this.options.eventsOptions.defaultAllDayEventDuration;
                    } else {
                        realDuration = oldState.end.diff(oldState.start);
                    }
                } else {
                    if (currentView.name === 'month') {
                        realDuration = oldState.end ? oldState.end.diff(oldState.start) : 0;
                    } else {
                        realDuration = this.options.eventsOptions.defaultAllDayEventDuration;
                    }
                }
            } else {
                if (oldState.allDay) {
                    realDuration = this.options.eventsOptions.defaultTimedEventDuration;
                } else {
                    realDuration = oldState.end ? oldState.end.diff(oldState.start) : 0;
                }
            }
            fcEvent.end = fcEvent.start.clone().tz(this.options.timezone, true).add(realDuration).tz('UTC', true);
            this.saveFcEvent(fcEvent);
        },

        saveFcEvent: function(fcEvent) {
            const promises = [];
            const format = this.MOMENT_BACKEND_FORMAT;
            const attrs = {
                allDay: fcEvent.allDay,
                start: fcEvent.start.clone().tz(this.options.timezone, true).utc().format(format)
            };

            if (fcEvent.end !== null) {
                attrs.end = fcEvent.end.clone().tz(this.options.timezone, true).utc().format(format);
            }

            const eventModel = this.collection.get(fcEvent.id);

            this.trigger('event:beforeSave', eventModel, promises, attrs);

            // wait for promises execution before save
            $.when(...promises)
                .done(this._saveFcEvent.bind(this, eventModel, attrs))
                .fail(this.updateEventsWithoutReload.bind(this));
        },

        _saveFcEvent: function(eventModel, attrs) {
            this.showSavingMask();
            try {
                eventModel.save(
                    attrs,
                    {
                        success: this._hideMask.bind(this),
                        error: (model, response) => {
                            this.showSaveEventError(response.responseJSON || {});
                            this._hideMask();
                        },
                        wait: true
                    }
                );
            } catch (err) {
                this.showSaveEventError(err);
            }
        },

        updateEvents: function() {
            try {
                this.showLoadingMask();
                // load events from a server
                this.collection.fetch({
                    reset: true,
                    success: () => {
                        this.updateEventsLoadedCache();
                        this.updateEventsWithoutReload();
                    },
                    error: (collection, response) => {
                        this.showLoadEventsError(response.responseJSON || {});
                        this._hideMask();
                    }
                });
            } catch (err) {
                this.showLoadEventsError(err);
            }
        },

        updateEventsLoadedCache: function() {
            this.eventsLoaded = {};
            this.options.connectionsOptions.collection.each(function(connectionModel) {
                if (connectionModel.get('visible')) {
                    this.eventsLoaded[connectionModel.get('calendarUid')] = true;
                }
            }, this);
        },

        updateEventsWithoutReload: function() {
            const oldEnableEventLoading = this.enableEventLoading;
            this.enableEventLoading = false;
            this.getCalendarElement().fullCalendar('refetchEvents');
            this.enableEventLoading = oldEnableEventLoading;
        },

        loadEvents: function(start, end, timezone, callback) {
            const onEventsLoad = () => {
                if (this.enableEventLoading || _.size(this.eventsLoaded) === 0) {
                    this.updateEventsLoadedCache();
                }

                // prepare them for full calendar
                const fcEvents = _.map(this.filterEvents(this.collection.models), function(eventModel) {
                    return this.createViewModel(eventModel);
                }, this);
                this._hideMask();
                callback(fcEvents);
            };
            start = start.tz(timezone, true).format(this.MOMENT_BACKEND_FORMAT);
            end = end.tz(timezone, true).format(this.MOMENT_BACKEND_FORMAT);
            try {
                this.collection.setRange(start, end);
                if (this.enableEventLoading) {
                    // load events from a server
                    this.collection.fetch({
                        reset: true,
                        success: onEventsLoad,
                        error: (collection, response) => {
                            callback({});
                            this.showLoadEventsError(response.responseJSON || {});
                        }
                    });
                } else {
                    // use already loaded events
                    onEventsLoad();
                }
            } catch (err) {
                callback({});
                this.showLoadEventsError(err);
            }
        },

        /**
         * Performs filtration of calendar events before they are rendered
         *
         * @param {Array} events
         *
         * @returns {Array}
         */
        filterEvents: function(events) {
            const visibleEvents = this.filterVisibleEvents(events);

            return this.filterCancelledEvents(visibleEvents);
        },

        filterVisibleEvents: function(events) {
            const visibleConnectionIds = [];
            // collect visible connections
            this.options.connectionsOptions.collection.each(function(connectionModel) {
                if (connectionModel.get('visible')) {
                    visibleConnectionIds.push(connectionModel.get('calendarUid'));
                }
            }, this);

            return _.filter(events, function(event) {
                return -1 !== _.indexOf(visibleConnectionIds, event.get('calendarUid'));
            });
        },

        filterCancelledEvents: function(events) {
            return _.filter(events, function(event) {
                return !event.get('isCancelled');
            });
        },

        /**
         * Creates event entry for rendering in calendar plugin from the given event model
         *
         * @param {Object} eventModel
         */
        createViewModel: function(eventModel) {
            const fcEvent = _.pick(
                eventModel.attributes,
                [
                    'id',
                    'title',
                    'start',
                    'end',
                    'allDay',
                    'backgroundColor',
                    'calendarUid',
                    'editable',
                    'startEditable',
                    'durationEditable'
                ]
            );
            const colors = this.colorManager.getCalendarColors(fcEvent.calendarUid);

            // set an event text and background colors the same as the owning calendar
            fcEvent.color = colors.backgroundColor;
            if (fcEvent.backgroundColor) {
                fcEvent.textColor = colorUtil.getContrastColor(fcEvent.backgroundColor);
            } else {
                fcEvent.textColor = colors.color;
            }

            if (fcEvent.start !== null && !moment.isMoment(fcEvent.start)) {
                fcEvent.start = $.fullCalendar.moment(fcEvent.start).tz(this.options.timezone);
            }

            if (fcEvent.end !== null && !moment.isMoment(fcEvent.end)) {
                const end = $.fullCalendar.moment(fcEvent.end);
                fcEvent.end = end.tz(this.options.timezone);

                if (fcEvent.allDay) {
                    fcEvent.endTime = end.time();
                } else {
                    fcEvent.endTime = null;
                }
            }

            if (fcEvent.end && fcEvent.end.diff(fcEvent.start) === 0) {
                fcEvent.end = null;
                fcEvent.endTime = null;
            }
            return fcEvent;
        },

        showSavingMask: function() {
            this.getLoadingMask().show(__('Saving...'));
        },

        showLoadingMask: function() {
            this.getLoadingMask().show(__('Loading...'));
        },

        _hideMask: function() {
            if (this.loadingMask) {
                this.loadingMask.hide();
            }
        },

        showLoadEventsError: function(err) {
            this._showError(__('Sorry, calendar events were not loaded correctly'), err);
        },

        showSaveEventError: function(err) {
            this._showError(__('Sorry, calendar event was not saved correctly'), err);
        },

        showMiscError: function(err) {
            this._showError(__('Sorry, an unexpected error has occurred.'), err);
        },

        showUpdateError: function(err) {
            this._showError(__('Sorry, the calendar update has failed.'), err);
        },

        _showError: function(message, err) {
            this._hideMask();
            messenger.showErrorMessage(message, err);
        },

        initCalendarContainer: function() {
            // init events container
            const eventsContainer = this.$el.find(this.options.eventsOptions.containerSelector);
            if (eventsContainer.length === 0) {
                throw new Error('Cannot find container selector "' +
                    this.options.eventsOptions.containerSelector + '" element.');
            }
            eventsContainer.empty();
            eventsContainer.append($(this.eventsTemplate()));
        },

        _prepareFullCalendarOptions: function() {
            let scrollTime;
            // prepare options for jQuery FullCalendar control
            const options = {// prepare options for jQuery FullCalendar control
                isRTL: _.isRTL(),
                timezone: this.options.timezone,
                selectLongPressDelay: 30,
                displayEventEnd: {
                    month: true
                },
                selectHelper: true,
                events: this.loadEvents.bind(this),
                select: this.onFcSelect.bind(this),
                eventClick: this.onFcEventClick.bind(this),
                eventDragStart: this.onFcEventDragStart.bind(this),
                eventDrop: this.onFcEventDrop.bind(this),
                eventResize: this.onFcEventResize.bind(this),
                loading: show => {
                    if (show) {
                        this.showLoadingMask();
                    } else {
                        this._hideMask();
                    }
                },
                views: {}
            };
            const keys = [
                'defaultDate', 'defaultView', 'editable', 'selectable',
                'header', 'allDayText', 'allDayHtml', 'allDaySlot', 'buttonText', 'selectLongPressDelay',
                'titleFormat', 'columnFormat', 'timeFormat', 'slotLabelFormat',
                'minTime', 'maxTime', 'scrollTime', 'slotEventOverlap',
                'firstDay', 'monthNames', 'monthNamesShort', 'dayNames',
                'dayNamesShort', 'aspectRatio', 'defaultAllDayEventDuration',
                'defaultTimedEventDuration', 'fixedWeekCount', 'eventOrder'
            ];
            _.extend(options, _.pick(this.options.eventsOptions, keys));
            if (!_.isUndefined(options.defaultDate)) {
                // Eliminated issue with timezone double correction:
                // 1) default timezone setting
                // 2) time offset in the value
                // Only default timezone from settings is taken in account now
                options.defaultDate = moment.unix(moment(options.defaultDate).unix());
            }

            if (!options.aspectRatio) {
                options.contentHeight = 'auto';
                options.height = 'auto';
            }

            if (this.options.scrollToCurrentTime) {
                scrollTime = moment.tz(this.options.timezone);
                if (scrollTime.minutes() < 10 && scrollTime.hours() !== 0) {
                    scrollTime.subtract(1, 'h');
                }
                options.scrollTime = scrollTime.startOf('hour').format('HH:mm:ss');
            }

            const dateFormat = localeSettings.getVendorDateTimeFormat('moment', 'date', 'MMM D, YYYY');
            const timeFormat = localeSettings.getVendorDateTimeFormat('moment', 'time', 'h:mm A');
            // prepare FullCalendar specific date/time formats
            const isDateFormatStartedWithDay = dateFormat[0] === 'D';
            const weekFormat = isDateFormatStartedWithDay ? 'D MMMM YYYY' : 'MMMM D YYYY';
            _.extend(options.views, {
                month: {
                    columnFormat: 'ddd',
                    titleFormat: 'MMMM YYYY'
                },
                week: {
                    columnFormat: 'ddd ' + dateFormat,
                    titleFormat: weekFormat
                },
                day: {
                    columnFormat: 'dddd ' + dateFormat,
                    titleFormat: 'dddd, ' + dateFormat
                }
            });
            options.timeFormat = timeFormat;
            options.smallTimeFormat = timeFormat;

            options.eventAfterAllRender = () => {
                const setTimeline = this.setTimeline.bind(this);
                _.delay(setTimeline);
                clearInterval(this.timelineUpdateIntervalId);
                this.timelineUpdateIntervalId = setInterval(setTimeline, 60 * 1000);
            };

            options.eventAfterRender = (fcEvent, $el) => {
                const event = this.collection.get(fcEvent.id);
                if (event) {
                    eventDecorator.decorate(event, $el);
                }
            };

            return options;
        },

        initializeFullCalendar: function() {
            const calendarElement = this.getCalendarElement();
            const options = this._prepareFullCalendarOptions();

            // create jQuery FullCalendar control
            calendarElement.fullCalendar(options);
            const fullCalendar = calendarElement.data('fullCalendar');
            // to avoid scroll blocking on mobile remove dragstart event listener that is added in calendar view
            if (_.isObject(fullCalendar) && tools.isMobile()) {
                $(document).off('dragstart', fullCalendar.getView().documentDragStartProxy);
            }
            this.updateLayout();
            this.enableEventLoading = true;
        },

        prepareConnectionsContainer: function() {
            let parentElement;

            if (this.options.connectionsOptions.modalContentTemplateId) {
                const modalContentTemplate = $('#' + this.options.connectionsOptions.modalContentTemplateId).html();
                const innerModalView = new BaseView({el: $(modalContentTemplate)});

                this.subview('innerModalView', innerModalView);
                innerModalView.render().initLayout();
                parentElement = innerModalView.$el;
            } else {
                parentElement = this.$el;
            }

            const connectionsContainer = parentElement.find(this.options.connectionsOptions.containerSelector);

            // init connections container
            if (connectionsContainer.length === 0) {
                throw new Error('Cannot find "' + this.options.connectionsOptions.containerSelector + '" element.');
            }

            return connectionsContainer;
        },

        initializeConnectionsView: function(connectionsContainer) {
            const connectionsTemplate = _.template($(this.options.connectionsOptions.containerTemplateSelector).html());

            connectionsContainer.html(connectionsTemplate());

            // create a view for a list of connections
            const connectionsView = new ConnectionView({
                el: connectionsContainer,
                collection: this.options.connectionsOptions.collection,
                calendar: this.options.calendar,
                itemTemplateSelector: this.options.connectionsOptions.itemTemplateSelector,
                colorManager: this.colorManager
            });

            this.subview('connectionsView', connectionsView);

            this.listenTo(connectionsView, 'connectionAdd', this.onConnectionAdded);
            this.listenTo(connectionsView, 'connectionChange', this.onConnectionChanged);
            this.listenTo(connectionsView, 'connectionRemove', this.onConnectionDeleted);
        },

        onConnectionsButtonClick: function() {
            let connectionModal = this.subview('connectionModal');

            if (!connectionModal) {
                connectionModal = new Modal({
                    autoRender: true,
                    content: this.subview('innerModalView'),
                    title: __('oro.calendar.select'),
                    allowOk: false,
                    className: 'modal oro-modal-normal modal--fullscreen-small-device',
                    disposeOnHidden: false
                });

                this.subview('connectionModal', connectionModal);
            }

            connectionModal.open();
        },

        onWidgetDialogOpen: function(dialogWidget) {
            const connectionModal = this.subview('connectionModal');

            if (connectionModal && connectionModal.isOpen() && !this.childDialogOfModal) {
                connectionModal.suspend();

                // Opened dialog can have a nested one so we need to store it for catching of its closing
                this.childDialogOfModal = dialogWidget;
            }
        },

        onWidgetDialogClose: function(dialogWidget) {
            const connectionModal = this.subview('connectionModal');

            if (connectionModal && connectionModal.isOpen() && this.childDialogOfModal === dialogWidget) {
                connectionModal.restore();
                this.childDialogOfModal = null;
            }
        },

        loadConnectionColors: function() {
            let lastBackgroundColor = null;
            this.getConnectionCollection().each(connection => {
                const obj = connection.toJSON();
                this.colorManager.applyColors(obj, function() {
                    return lastBackgroundColor;
                });
                this.colorManager.setCalendarColors(obj.calendarUid, obj.backgroundColor);
                if (['user', 'system', 'public'].indexOf(obj.calendarAlias) !== -1) {
                    lastBackgroundColor = obj.backgroundColor;
                }
            });
        },

        render: function() {
            // init views
            this.initCalendarContainer();
            if (_.isUndefined(this.options.connectionsOptions.containerTemplateSelector)) {
                this.loadConnectionColors();
            } else {
                const connectionsContainer = this.prepareConnectionsContainer();

                this.initializeConnectionsView(connectionsContainer);
            }
            // initialize jQuery FullCalendar control
            this.initializeFullCalendar();
            return this;
        },

        setTimeline: function() {
            let timelineElement;
            let dayCol;
            const calendarElement = this.getCalendarElement();
            const currentView = calendarElement.fullCalendar('getView');

            if (this.options.eventsOptions.recoverView) {
                this.persistView();
            }

            // shown interval in calendar timezone
            const shownInterval = {
                start: currentView.intervalStart.clone().utc(),
                end: currentView.intervalEnd.clone().utc()
            };
            // current time in calendar timezone
            const now = moment.tz(this.options.timezone);

            if (currentView.name === 'month') {
                // nothing to do
                return;
            }

            // this function is called every 1 minute
            if (now.hours() === 0 && now.minutes() <= 2) {
                // the day has changed
                calendarElement.find('.fc-today')
                    .removeClass('fc-today fc-state-highlight')
                    .next()
                    .addClass('fc-today fc-state-highlight');
            }

            const timeGrid = calendarElement.find('.fc-time-grid');
            timelineElement = timeGrid.children('.timeline-marker');
            if (timelineElement.length === 0) {
                // if timeline isn't there, add it
                timelineElement = $('<hr class="timeline-marker">');
                timeGrid.prepend(timelineElement);
            }

            if (shownInterval.start.isBefore(now) && shownInterval.end.isAfter(now)) {
                timelineElement.show();
            } else {
                timelineElement.hide();
            }

            const curSeconds = (now.hours() * 3600) + (now.minutes() * 60) + now.seconds();
            const percentOfDay = curSeconds / 86400; // 24 * 60 * 60 = 86400, # of seconds in a day
            const timelineTop = Math.floor(timeGrid.height() * percentOfDay);
            timelineElement.css('top', timelineTop + 'px');

            if (currentView.name === 'agendaWeek') {
                // week view, don't want the timeline to go the whole way across
                dayCol = calendarElement.find('.fc-today:visible');
                if (dayCol.length !== 0 && dayCol.position() !== null) {
                    timelineElement.css({
                        left: (dayCol.position().left) + 'px',
                        width: (dayCol.width() + 3) + 'px'
                    });
                }
            }
        },

        persistView: function() {
            const calendarElement = this.getCalendarElement();
            const currentDate = calendarElement.fullCalendar('getDate');
            const currentView = calendarElement.fullCalendar('getView');
            const viewKey = this.getStorageKey('defaultView');
            const dateKey = this.getStorageKey('defaultDate');

            if (this.options.eventsOptions.recoverView) {
                persistentStorage.setItem(viewKey, currentView.name);

                if (!isNaN(currentDate)) {
                    persistentStorage.setItem(dateKey, currentDate.unix());
                }
            }
        },

        getAvailableHeight: function() {
            const $fcView = this.getCalendarElement().find('.fc-view:first');
            return mediator.execute('layout:getAvailableHeight', $fcView);
        },

        /**
         * Chooses layout on resize or during creation
         */
        updateLayout: function() {
            if (this.options.eventsOptions.aspectRatio) {
                this.setLayout('default');
                // do nothing
                return;
            }
            const $fcView = this.getCalendarElement().find('.fc-view:first');
            const $sidebar = $('[data-role="calendar-sidebar"]');
            let preferredLayout = mediator.execute('layout:getPreferredLayout', $fcView);
            if (preferredLayout === 'fullscreen' &&
                $sidebar.height() > mediator.execute('layout:getAvailableHeight', $sidebar)) {
                preferredLayout = 'scroll';
            }
            this.setLayout(preferredLayout);
        },

        /**
         * Sets layout and perform all required operations
         */
        setLayout: function(newLayout) {
            if (newLayout === this.layout) {
                if (newLayout === 'fullscreen') {
                    this.getCalendarElement().fullCalendar('option', 'contentHeight', this.getAvailableHeight());
                }
                return;
            }
            this.layout = newLayout;
            const $calendarEl = this.getCalendarElement();
            let contentHeight = '';
            let height = '';
            switch (newLayout) {
                case 'fullscreen':
                    mediator.execute('layout:disablePageScroll', $calendarEl);
                    contentHeight = this.getAvailableHeight();
                    break;
                case 'scroll':
                    height = 'auto';
                    contentHeight = 'auto';
                    mediator.execute('layout:enablePageScroll');
                    break;
                case 'default':
                    mediator.execute('layout:enablePageScroll');
                    // default values
                    break;
                default:
                    throw new Error('Unknown calendar layout');
            }
            $calendarEl.fullCalendar('option', 'height', height);
            $calendarEl.fullCalendar('option', 'contentHeight', contentHeight);
        },

        getStorageKey: function(item) {
            const calendarId = this.options.calendar;

            return calendarId ? item + calendarId : '';
        }
    });

    return CalendarView;
});
