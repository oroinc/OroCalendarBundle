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
            this.$('[data-name="control-section"]').each(_.bind(function(index, section) {
                var $section = $(section);
                var isDisabled = !$section.find('input[type=radio]').prop('checked');
                this.$('[data-type="datetime"]').each(_.bind(function(index, element) {
                    var dateTimePickerView = $(element).data('date-time-picker-view');
                    if (dateTimePickerView) {
                        dateTimePickerView.setDisabled(isDisabled);
                    }
                }, this));
                $section.find('input[data-name="value"]').prop('disabled', isDisabled);
            }, this));
        },

        render: function() {
            SwitchableRecurrenceSubview.__super__.render.call(this);
            this.updateControlSectionsState();
            return this;
        },

        findDataInputs: function() {
            return this.$('input[type=radio]:checked')
                .closest('[data-name="control-section"]').find(':input[data-name="value"]');
        }
    });

    return SwitchableRecurrenceSubview;
});
