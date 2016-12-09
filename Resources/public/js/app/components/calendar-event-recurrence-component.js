define(function(require) {
    'use strict';

    var CalendarEventRecurrenceComponent;
    var _ = require('underscore');
    var EventRecurrenceView = require('orocalendar/js/calendar/event/recurrence/event-recurrence-view');
    var EventRecurrenceModel = require('orocalendar/js/calendar/event/recurrence/event-recurrence-model');
    var BaseComponent = require('oroui/js/app/components/base/component');

    CalendarEventRecurrenceComponent = BaseComponent.extend({
        /** @type {Object|null} */
        _initModelData: null,

        /** @type {Boolean} */
        _eventEditFormIsChanged: false,

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var modelAttrs = options.modelAttrs;
            if (this.model) {
                // if there's event model, take recurrence attributes from there
                modelAttrs = this.model.get('recurrence');
            }
            var viewOptions = this._prepareEventRecurrenceViewOptions(options);
            this.recurrenceModel = this._initEventRecurrenceModel(modelAttrs);
            this.recurrenceView = this._initEventRecurrenceView(_.extend(viewOptions, {
                model: this.recurrenceModel
            }));
            this._initModelData = this.recurrenceModel.toJSON();
            this.listenTo(this.recurrenceView, 'formChanged', this._handleFormChange);
            this.listenTo(this.recurrenceView, 'formSubmit', this._handleFormSubmit);

        },

        /**
         * Initializes EventRecurrenceModel
         *
         * @param {Object} attrs
         * @protected
         */
        _initEventRecurrenceModel: function(attrs) {
            if (attrs) {
                // sometimes dayOfWeek comes from server as object, we have to convert is into array
                attrs.dayOfWeek = _.values(attrs.dayOfWeek);
            }
            return new EventRecurrenceModel(attrs);
        },

        /**
         * Initializes EventRecurrenceView
         *
         * @param {Object} options
         * @protected
         */
        _initEventRecurrenceView: function(options) {
            options.autoRender = true;
            return new EventRecurrenceView(options);
        },

        /**
         * Prepares options for a recurrence view on a base of component's options
         *
         * @param {Object} options
         * @protected
         */
        _prepareEventRecurrenceViewOptions: function(options) {
            return _.extend(_.pick(options, 'inputNamePrefixes', 'errors'), {
                el: options._sourceElement
            });
        },

        _handleFormChange: function() {
            this._eventEditFormIsChanged = true;
        },

        _handleFormSubmit: function() {
            var eventModel = this.model;
            if (
                !eventModel || // if not event model -- nothing to refresh
                !this._eventEditFormIsChanged || // if not touched -- no need to refresh
                // initially recurrent as turned off and it stays the same -- no need to refresh
                this.recurrenceModel.isEmptyRecurrence() && this.recurrenceModel.isEqual(this._initModelData)
            ) {
                return;
            }
            eventModel.off('sync',  null, this);
            eventModel.once('sync', function() {
                eventModel.collection.trigger('toRefresh');
            }, this);
        }
    });

    return CalendarEventRecurrenceComponent;
});
