import $ from 'jquery';
import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';
import DatepairView from 'oroform/js/app/views/datepair-view';
import AllDayView from 'orocalendar/js/app/views/all-day-view';

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

export default CalendarEventDateRangeComponent;
