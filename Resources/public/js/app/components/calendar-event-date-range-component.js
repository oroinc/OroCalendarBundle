define(function(require) {
    'use strict';

    var CalendarEventDateRangeComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DatepairView = require('oroform/js/app/views/datepair-view');
    var AllDayView = require('orocalendar/js/app/views/all-day-view');

    CalendarEventDateRangeComponent = BaseComponent.extend({
        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var subPromises = _.values(options._subPromises);
            var opts = _.omit(options, this.AUXILIARY_OPTIONS);
            opts.el = options._sourceElement;

            this._deferredInit();
            $.when.apply($, _.compact(subPromises)).then(_.bind(function() {
                this.handleLayoutInit(opts);
                this._resolveDeferredInit();
            }, this));
        },

        handleLayoutInit: function(options) {
            this.datepairView = new DatepairView(options);
            this.allDayview = new AllDayView(options);
        }
    });

    return CalendarEventDateRangeComponent;
});
