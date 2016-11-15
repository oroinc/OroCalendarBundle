define(function(require) {
    'use strict';

    var AbstractRecurrenceSubview;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    AbstractRecurrenceSubview = BaseView.extend(/** @exports AbstractRecurrenceSubview.prototype */{
        /** @type {boolean} */
        isActive: true,

        events: {
            'change :input[data-related-field]': 'updateModel'
        },

        initialize: function(options) {
            if ('relatedFields' in this === false) {
                throw new Error('Property "relatedFields" should be declare in successor class');
            }
            _.extend(this, _.pick(options, 'isActive'));
            AbstractRecurrenceSubview.__super__.initialize.call(this, options);
        },

        render: function() {
            AbstractRecurrenceSubview.__super__.render.call(this);
            this.$el.toggle(this.isActive);
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
            return $context.find(':input[data-related-field]').filter(_.bind(function(index, element) {
                return _.contains(this.relatedFields, $(element).attr('data-related-field'));
            }, this));
        },

        getValue: function() {
            var value = _.mapObject(_.pick(this.model.defaults, this.relatedFields), _.clone);
            if (this.isActive) {
                this.dataInputs().each(function() {
                    value[$(this).data('related-field')] = $(this).val() || null;
                });
            }
            return value;
        },

        updateModel: function() {
            this.model.set(this.getValue());
        },

        toggle: function(state) {
            this.isActive = state;
            this.$el.toggle(state);
            this.updateModel();
        }
    });

    return AbstractRecurrenceSubview;
});
