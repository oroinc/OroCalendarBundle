define([
    'jquery',
    'oroui/js/app/views/base/view'
], function($, BaseView) {
    'use strict';

    var CalendarEventAllDayView;
    CalendarEventAllDayView = BaseView.extend({

        /**
         * Options
         */
        options: {},

        autoRender: true,

        events: {
            'change input[name$="[allDay]"]': 'onAllDayChange'
        },

        startAtTimeElement: null,
        oldStartAtValue: null,

        endAtTimeElement: null,
        oldEndAtValue: null,

        /**
         * @inheritDoc
         */
        constructor: function CalendarEventAllDayView() {
            CalendarEventAllDayView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            this.handleAllDayEventFlag(this.$('input[name$="[allDay]"]'), 0);
        },

        onAllDayChange: function(event) {
            this.handleAllDayEventFlag($(event.target), 200);
        },

        handleAllDayEventFlag: function(allDayEventElement, animationDuration) {
            if (!this.startAtTimeElement) {
                var startAtElements = this.$('input[name$="[start]"]').closest('.control-group-datetime');
                this.startAtTimeElement = startAtElements.find('.timepicker-input');
            }
            if (!this.endAtTimeElement) {
                var endAtElements = this.$('input[name$="[end]"]').closest('.control-group-datetime');
                this.endAtTimeElement = endAtElements.find('.timepicker-input');
            }
            if (allDayEventElement.prop('checked')) {
                this.oldStartAtValue = this.startAtTimeElement.timepicker('getTime');
                this.oldEndAtValue = this.endAtTimeElement.timepicker('getTime');

                this.startAtTimeElement.hide(animationDuration, function() {
                    $(this).timepicker('setTime', 0).trigger('change');
                });
                this.endAtTimeElement.hide(animationDuration, function() {
                    $(this).timepicker('setTime', '11:59pm').trigger('change');
                });
            } else {
                if (this.oldStartAtValue) {
                    this.startAtTimeElement.timepicker('setTime', this.oldStartAtValue).trigger('change');
                }
                if (this.oldEndAtValue) {
                    this.endAtTimeElement.timepicker('setTime', this.oldEndAtValue).trigger('change');
                }

                this.startAtTimeElement.show(animationDuration);
                this.endAtTimeElement.show(animationDuration);
            }
        }
    });

    return CalendarEventAllDayView;
});
