define(function(require) {
    'use strict';

    var AbstractRecurrenceSubview;
    var _ = require('underscore');
    var $ = require('jquery');
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var BaseView = require('oroui/js/app/views/base/view');

    AbstractRecurrenceSubview = BaseView.extend(/** @exports AbstractRecurrenceSubview.prototype */{
        autoRender: true,
        events: {
            'change input[type=radio]': 'onSectionSwitch'
        },

        initialize: function() {
            if ('defaultData' in this === false) {
                throw new Error('Property "defaultData" should be declare in successor class');
            }
            AbstractRecurrenceSubview.__super__.render.apply(this, arguments);
        },

        getTemplateData: function() {
            var data = AbstractRecurrenceSubview.__super__.getTemplateData.apply(this, arguments);
            _.defaults(data, this.defaultData);
            return data;
        },

        render: function() {
            AbstractRecurrenceSubview.__super__.render.call(this);
            this.$('[data-type="datetime"]').each(_.bind(function(index, element) {
                var dateTimePickerView = new DateTimePickerView({
                    el: element
                });
                var subviewName = 'date-time-picker-' + index;
                this.subview(subviewName, dateTimePickerView);
                $(element).closest('[data-name="control-section"]').data('date-time-picker-subview-name', subviewName);
            }, this));
            this.updateControlSectionsState();
            return this;
        },

        onSectionSwitch: function(e) {
            this.$("input[type=radio]").not(e.target).prop('checked', false);
            this.updateControlSectionsState();
        },

        updateControlSectionsState: function() {
            this.$('[data-name="control-section"]').each(_.bind(function(index, section) {
                var $section = $(section);
                var isDisabled = !$section.find('input[type=radio]').prop('checked');
                var datetimeSubviewName = $section.data('date-time-picker-subview-name');
                if (datetimeSubviewName) {
                    this.subview(datetimeSubviewName).setDisabled(isDisabled);
                } else {
                    $section.find('input[data-name="value"]').prop('disabled', isDisabled);
                }
            }, this));
        },

        getValue: function() {
            var value = _.clone(this.defaultData);
            var $activeInput = this.$('input[type=radio]:checked')
                .closest('[data-name="control-section"]').find(':input[data-name="value"]');
            if ($activeInput.length) {
                $activeInput.each(function() {
                    value[$(this).data('field')] = $(this).val() || null;
                });
            }
            return value;
        }
    });

    return AbstractRecurrenceSubview;
});
