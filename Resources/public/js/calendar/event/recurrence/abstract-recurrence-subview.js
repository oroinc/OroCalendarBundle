define(function(require) {
    'use strict';

    var AbstractRecurrenceSubview;
    var _ = require('underscore');
    var $ = require('jquery');
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var BaseView = require('oroui/js/app/views/base/view');

    AbstractRecurrenceSubview = BaseView.extend(/** @exports AbstractRecurrenceSubview.prototype */{
        initialize: function() {
            if ('defaultData' in this === false) {
                throw new Error('Property "defaultData" should be declare in successor class');
            }
            AbstractRecurrenceSubview.__super__.render.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = AbstractRecurrenceSubview.__super__.getTemplateData.apply(this, arguments);
            _.defaults(data, this.defaultData);
            return data;
        },

        render: function() {
            AbstractRecurrenceSubview.__super__.render.call(this);
            this.$('[data-type="datetime"]').each(_.bind(function(index, element) {
                var dateTimePickerView = new DateTimePickerView({
                    el: element
                });
                this.subview('date-time-picker-' + index, dateTimePickerView);
                $(element).data('date-time-picker-view', dateTimePickerView);
            }, this));
            return this;
        },
        /**
         * Finds inputs where stored data to save
         *
         * @return {jQuery}
         */
        dataInputs: function() {
            return this.findDataInputs(this.$el);
        },

        findDataInputs: function($context) {
            return $context.find(':input[data-name]').filter(_.bind(function(index, element) {
                return $(element).attr('data-name') in this.defaultData;
            }, this));
        },

        getValue: function() {
            var value = _.clone(this.defaultData);
            this.dataInputs().each(function() {
                value[$(this).data('name')] = $(this).val() || null;
            });
            return value;
        }
    });

    return AbstractRecurrenceSubview;
});
