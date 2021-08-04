define(function(require) {
    'use strict';

    const $ = require('jquery');
    const AbstractRecurrenceSubview = require('orocalendar/js/calendar/event/recurrence/abstract-recurrence-subview');

    const SwitchableRecurrenceSubview = AbstractRecurrenceSubview.extend({
        RADIOBUTTON_SELECTOR: '[data-role="control-section-switcher"] input[type="radio"]',

        SECTION_SELECTOR: '[data-role="control-section-switcher"]',

        events: {
            'change [data-role="control-section-switcher"] input[type="radio"]': 'onSectionSwitch',
            'mousedown [data-role="control-section-switcher"]': 'onSectionSwitchMousedown'
        },

        /**
         * @inheritdoc
         */
        constructor: function SwitchableRecurrenceSubView(options) {
            SwitchableRecurrenceSubview.__super__.constructor.call(this, options);
        },

        onSectionSwitchMousedown: function(e) {
            // switches radio buttons on mousedown to be ahead of blur event of other input
            // to disable them first before validation message is shown
            const $radio = this.$(e.target).closest(this.SECTION_SELECTOR).find('input[type="radio"]');
            this.$('input[type=radio]').not($radio).prop('checked', false);
            $radio.prop('checked', true).trigger('change');
        },

        onSectionSwitch: function(e) {
            this.$('input[type=radio]').not(e.target).prop('checked', false);
            this.updateControlSectionsState();
            this.updateModel();
        },

        updateControlSectionsState: function() {
            this.$('[data-name="control-sections"]').children().each((index, section) => {
                const $section = $(section);
                const isDisabled = !$section.find(this.RADIOBUTTON_SELECTOR).prop('checked');
                this.setInputsDisabled(this.findDataInputs($section), isDisabled);
            });
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
            const $activeSection = this.$(this.RADIOBUTTON_SELECTOR + ':checked')
                .closest('[data-name="control-sections"] > *');
            return this.findDataInputs($activeSection);
        }
    });

    return SwitchableRecurrenceSubview;
});
