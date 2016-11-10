define(function(require) {
    'use strict';

    var RecurrenceEndsView;
    var _ = require('underscore');
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var BaseView = require('oroui/js/app/views/base/view');

    RecurrenceEndsView = BaseView.extend(/** @exports RecurrenceEndsView.prototype */{
        autoRender: true,
        template: require('tpl!orocalendar/templates/event/recurrence/recurrence-ends.html'),
        events: {
            'change input[type=radio]': 'onModeSwitch'
        },

        initialize: function(options) {
            this.options = options;
            _.extend(this, _.pick(options, []));
            RecurrenceEndsView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = RecurrenceEndsView.__super__.getTemplateData.apply(this, arguments);
            return data;
        },

        render: function() {
            RecurrenceEndsView.__super__.render.call(this);
            this.subview('date-time-picker', new DateTimePickerView({
                el: this.$('[data-name="datetimepicker"]')
            }));
            return this;
        },

        onModeSwitch: function(e) {
            this.$("input[type=radio]").not(e.target).prop('checked', false);
        }
    });

    return RecurrenceEndsView;
});
