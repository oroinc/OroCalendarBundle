define(function(require) {
    'use strict';

    var ActionTargetSelectView;
    var BaseView = require('oroui/js/app/views/base/view');

    ActionTargetSelectView = BaseView.extend({
        className: 'widget-content',
        template: require('tpl!orocalendar/templates/calendar/event/action-target-select.html'),
        actionType: null,
        initialize: function(options) {
            this.actionType = options.actionType;
            ActionTargetSelectView.__super__.initialize.call(this, options);
        },
        getTemplateData: function() {
            var data = ActionTargetSelectView.__super__.getTemplateData.call(this);
            data.actionType = this.actionType;
            return data;
        },
        getValue: function() {
            return this.$el.find('[name="action-target"]:checked').val();
        }
    });

    return ActionTargetSelectView;
});

