define(function(require) {
    'use strict';

    var SwitchableRecurrenceSubview;
    var _ = require('underscore');
    var $ = require('jquery');
    var AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    SwitchableRecurrenceSubview = AbstractRecurrenceSubview.extend({
        RADIOBUTTON_SELECTOR: '[data-role="control-section-switcher"]>input[type="radio"]',
        SECTION_SELECTOR: '[data-role="control-section-switcher"]',

        events: {
            'change [data-role="control-section-switcher"]>input[type="radio"]': 'onSectionSwitch',
            'mousedown [data-role="control-section-switcher"]': 'onSectionSwitchMousedown'
        },

        onSectionSwitchMousedown: function(e) {
            // switches radio buttons on mousedown to be ahead of blur event of other input
            // to disable them first before validation message is shown
            var $radio = this.$(e.target).closest(this.SECTION_SELECTOR).find('input[type="radio"]');
            this.$('input[type=radio]').not($radio).prop('checked', false);
            $radio.prop('checked', true).trigger('change');
        },

        onSectionSwitch: function(e) {
            this.$('input[type=radio]').not(e.target).prop('checked', false);
            this.updateControlSectionsState();
            this.updateModel();
        },

        updateControlSectionsState: function() {
            this.$('[data-name="control-sections"]').children().each(_.bind(function(index, section) {
                var $section = $(section);
                var isDisabled = !$section.find(this.RADIOBUTTON_SELECTOR).prop('checked');
                this.setInputsDisabled(this.findDataInputs($section), isDisabled);
            }, this));
        },

        setInputsDisabled: function($inputs, isDisabled) {
            $inputs.prop('disabled', isDisabled).trigger(isDisabled ? 'disabled' : 'enabled');
        },

        render: function() {
            SwitchableRecurrenceSubview.__super__.render.call(this);
            this.updateControlSectionsState();
            return this;
        },

        dataInputs: function() {
            var $activeSection = this.$(this.RADIOBUTTON_SELECTOR + ':checked')
                .closest('[data-name="control-sections"] > *');
            return this.findDataInputs($activeSection);
        }
    });

    return SwitchableRecurrenceSubview;
});
