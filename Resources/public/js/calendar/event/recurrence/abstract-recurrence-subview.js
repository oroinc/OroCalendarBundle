define(function(require) {
    'use strict';

    var AbstractRecurrenceSubview;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    AbstractRecurrenceSubview = BaseView.extend(/** @exports AbstractRecurrenceSubview.prototype */{
        initialize: function(options) {
            if ('relatedFields' in this === false) {
                throw new Error('Property "relatedFields" should be declare in successor class');
            }
            AbstractRecurrenceSubview.__super__.initialize.call(this, options);
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
            return $context.find(':input[data-related-field]').filter(_.bind(function(index, element) {
                return _.contains(this.relatedFields, $(element).attr('data-related-field'));
            }, this));
        },

        getValue: function() {
            var value = _.pick(this.model.attributes, this.relatedFields);
            this.dataInputs().each(function() {
                value[$(this).data('related-field')] = $(this).val() || null;
            });
            return value;
        }
    });

    return AbstractRecurrenceSubview;
});
