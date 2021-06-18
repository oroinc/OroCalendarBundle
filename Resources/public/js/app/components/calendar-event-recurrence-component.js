define(function(require) {
    'use strict';

    const _ = require('underscore');
    const EventRecurrenceView = require('orocalendar/js/calendar/event/recurrence/event-recurrence-view');
    const EventRecurrenceModel = require('orocalendar/js/calendar/event/recurrence/event-recurrence-model');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const CalendarEventRecurrenceComponent = BaseComponent.extend({
        /** @type {Backbone.Event|null} */
        commonEventBus: null,

        /**
         * @inheritdoc
         */
        constructor: function CalendarEventRecurrenceComponent(options) {
            CalendarEventRecurrenceComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            let modelAttrs = options.modelAttrs;
            if (this.model) {
                // if there's event model, take recurrence attributes from there
                modelAttrs = this.model.get('recurrence');
            }
            _.extend(this, _.pick(options, 'commonEventBus'));
            const viewOptions = this._prepareEventRecurrenceViewOptions(options);
            this.recurrenceView = this._initEventRecurrenceView(_.extend(viewOptions, {
                model: this._initEventRecurrenceModel(modelAttrs)
            }));
            this.listenTo(this.recurrenceView, 'formChanged', this._handleFormChange);
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
        }
    });

    return CalendarEventRecurrenceComponent;
});
