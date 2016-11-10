define(function(require) {
    'use strict';

    var RecurrenceEndsView;
    var _ = require('underscore');
    var $ = require('jquery');
    var DateTimePickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');
    var BaseView = require('oroui/js/app/views/base/view');

    RecurrenceEndsView = BaseView.extend(/** @exports RecurrenceEndsView.prototype */{
        autoRender: true,
        template: require('tpl!orocalendar/templates/event/recurrence/recurrence-ends.html'),
        events: {
            'change input[type=radio]': 'onSectionSwitch'
        },

        getTemplateData: function() {
            var data = RecurrenceEndsView.__super__.getTemplateData.apply(this, arguments);
            _.defaults(data, {
                occurrences: null,
                endTime: null
            });
            return data;
        },

        render: function() {
            RecurrenceEndsView.__super__.render.call(this);
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
            var value = {
                occurrences: null,
                endTime: null
            }
            var $activeInput = this.$('input[type=radio]:checked')
                .closest('[data-name="control-section"]').find('input[data-name="value"]');
            if ($activeInput.length) {
                value[$activeInput.data('field')] = $activeInput.val() || null;
            }
            return value;
        }
    });

    return RecurrenceEndsView;
});
