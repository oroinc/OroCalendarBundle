define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const DatepairView = require('oroform/js/app/views/datepair-view');
    const AllDayView = require('orocalendar/js/app/views/all-day-view');

    const CalendarEventDateRangeComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function CalendarEventDateRangeComponent(options) {
            CalendarEventDateRangeComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            const subPromises = _.values(options._subPromises);
            const opts = _.omit(options, this.AUXILIARY_OPTIONS);
            opts.el = options._sourceElement;

            this._deferredInit();
            $.when(..._.compact(subPromises)).then(() => {
                this.handleLayoutInit(opts);
                this._resolveDeferredInit();
            });
        },

        handleLayoutInit: function(options) {
            this.datepairView = new DatepairView(options);
            this.allDayview = new AllDayView(options);
        }
    });

    return CalendarEventDateRangeComponent;
});
