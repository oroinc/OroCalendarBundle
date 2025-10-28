import $ from 'jquery';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';

const ChangeStatusView = BaseView.extend({

    triggerEventName: '',

    events: {
        click: 'sendUpdate'
    },

    /**
     * @inheritdoc
     */
    constructor: function ChangeStatusView(options) {
        ChangeStatusView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        ChangeStatusView.__super__.initialize.call(this, options);
        this.triggerEventName = _.isEmpty(options.triggerEventName) ? '' : options.triggerEventName;
    },

    sendUpdate: function(e) {
        e.preventDefault();
        const triggerEventName = this.triggerEventName;
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

export default ChangeStatusView;
