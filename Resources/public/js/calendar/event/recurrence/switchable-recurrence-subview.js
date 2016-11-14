define(function(require) {
    'use strict';

    var SwitchableRecurrenceSubview;
    var _ = require('underscore');
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    SwitchableRecurrenceSubview = AbstractRecurrenceSubview.extend({
        events: {
            'change [data-role="control-section-switcher"]': 'onSectionSwitch'
        },

        onSectionSwitch: function(e) {
            this.$("input[type=radio]").not(e.target).prop('checked', false);
            this.updateControlSectionsState();
        },

        updateControlSectionsState: function() {
            this.$('[data-name="control-sections"]').children().each(_.bind(function(index, section) {
                var $section = $(section);
                var isDisabled = !$section.find('[data-role="control-section-switcher"]').prop('checked');
                this.setInputsDisabled(this.findDataInputs($section), isDisabled);
            }, this));
        },

        setInputsDisabled: function($inputs, isDisabled) {
            $inputs.prop('disabled', isDisabled);
        },

        render: function() {
            SwitchableRecurrenceSubview.__super__.render.call(this);
            this.updateControlSectionsState();
            return this;
        },

        dataInputs: function() {
            var $activeSection = this.$('[data-role="control-section-switcher"]:checked')
                .closest('[data-name="control-sections"] > *');
            return this.findDataInputs($activeSection);
        }
    });

    return SwitchableRecurrenceSubview;
});
