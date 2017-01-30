define(function(require) {
    'use strict';

    var ActionTargetSelectView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionTargetSelectView = BaseView.extend({
        template: require('tpl!orocalendar/templates/calendar/event/action-target-select.html'),
        actionType: null,

        /**
         * @todo This property should be removed in CRM-6758.
         * @property {bool}
         */
        restrictOnlyThisEventAction: null,

        initialize: function(options) {
            options = _.defaults(options, {restrictOnlyThisEventAction: false});

            this.actionType = options.actionType;
            this.restrictOnlyThisEventAction = options.restrictOnlyThisEventAction;
            ActionTargetSelectView.__super__.initialize.call(this, options);
        },
        getTemplateData: function() {
            var data = ActionTargetSelectView.__super__.getTemplateData.call(this);
            data.actionType = this.actionType;
            data.restrictOnlyThisEventAction = this.restrictOnlyThisEventAction;
            return data;
        },
        getValue: function() {
            return this.$el.find('[name="action-target"]:checked').val();
        }
    });

    return ActionTargetSelectView;
});
