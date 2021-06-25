define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const AbstractRecurrenceSubview = BaseView.extend(/** @exports AbstractRecurrenceSubview.prototype */{
        /** @type {boolean} */
        _isEnabled: true,

        events: {
            'change :input[data-related-field]': 'updateModel'
        },

        /**
         * @inheritdoc
         */
        constructor: function AbstractRecurrenceSubview(options) {
            AbstractRecurrenceSubview.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if ('relatedFields' in this === false) {
                throw new Error('Property "relatedFields" should be declare in successor class');
            }
            if ('enabled' in options) {
                this._isEnabled = Boolean(options.enabled);
            }
            AbstractRecurrenceSubview.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            const data = AbstractRecurrenceSubview.__super__.getTemplateData.call(this);
            if (!this._isEnabled) {
                _.extend(data, this.getDefaultValues());
            }
            return data;
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
            return $context.find(':input[data-related-field]').filter((index, element) => {
                return _.contains(this.relatedFields, $(element).attr('data-related-field'));
            });
        },

        getValue: function() {
            const value = this.getDefaultValues();
            this.dataInputs().each(function() {
                value[$(this).data('related-field')] = $(this).val() || null;
            });
            return value;
        },

        getDefaultValues: function() {
            return _.mapObject(_.pick(this.model.defaults, this.relatedFields), _.clone);
        },

        updateModel: function() {
            this.model.set(this.getValue());
        },

        resetModel: function() {
            this.model.set(this.getDefaultValues());
        },

        enable: function() {
            this.$el.show();
            this.updateModel();
            this._isEnabled = true;
        },

        disable: function() {
            this.resetModel();
            this.$el.hide();
            this._isEnabled = false;
        },

        isEnabled: function() {
            return this._isEnabled;
        }
    });

    return AbstractRecurrenceSubview;
});
