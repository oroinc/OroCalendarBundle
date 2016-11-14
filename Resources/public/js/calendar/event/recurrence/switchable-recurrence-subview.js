define(function(require) {
    'use strict';

    var SwitchableRecurrenceSubview;
    var _ = require('underscore');
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    SwitchableRecurrenceSubview = AbstractRecurrenceSubview.extend({
        events: {
            'change input[type=radio]': 'onSectionSwitch'
        },

        onSectionSwitch: function(e) {
            this.$("input[type=radio]").not(e.target).prop('checked', false);
            this.updateControlSectionsState();
        },

        updateControlSectionsState: function() {
            this.$('[data-name="control-sections"]').children().each(_.bind(function(index, section) {
                var $section = $(section);
                var isDisabled = !$section.find('input[type=radio]').prop('checked');
                this.$('[data-type="datetime"]').each(_.bind(function(index, element) {
                    var dateTimePickerView = $(element).data('date-time-picker-view');
                    if (dateTimePickerView) {
                        dateTimePickerView.setDisabled(isDisabled);
                    }
                }, this));
                this.findDataInputs($section).prop('disabled', isDisabled);
            }, this));
        },

        render: function() {
            SwitchableRecurrenceSubview.__super__.render.call(this);
            this.updateControlSectionsState();
            return this;
        },

        dataInputs: function() {
            var $activeSection = this.$('input[type=radio]:checked').closest('[data-name="control-sections"] > *');
            return this.findDataInputs($activeSection);
        }
    });

    return SwitchableRecurrenceSubview;
});
