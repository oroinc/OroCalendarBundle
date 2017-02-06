define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/app/views/base/view'
], function($, _, __, mediator, BaseView) {
    'use strict';

    var ChangeStatusView = BaseView.extend({

        triggerEventName: '',

        events: {
            click: 'sendUpdate'
        },

        initialize: function(options) {
            ChangeStatusView.__super__.initialize.call(this, options);
            this.triggerEventName = _.isEmpty(options.triggerEventName) ? '' : options.triggerEventName;
        },

        sendUpdate: function(e) {
            e.preventDefault();
            var triggerEventName = this.triggerEventName;
            $.ajax({
                url: this.$el.attr('href'),
                type: 'POST',
                success: function() {
                    if (_.isEmpty(triggerEventName)) {
                        mediator.execute('refreshPage');
                    } else {
                        mediator.trigger(triggerEventName);
                    }
                }
            });
        }
    });

    return ChangeStatusView;
});
