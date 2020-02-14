define([
    'underscore',
    'jquery',
    'orotranslation/js/translator'
], function(_, $, __) {
    'use strict';

    const defaultParam = {
        message: 'This date should be earlier than End date'
    };

    /**
     * @export orocalendar/js/validator/dateearlierthan
     */
    return [
        'Oro\\Bundle\\CalendarBundle\\Validator\\Constraints\\DateEarlierThan',
        function(value, element, options) {
            /**
             * For example if elementId == orocrm_campaign_form_startDate and options.field == endDate
             * then comparedElId will be orocrm_campaign_form_endDate
             */
            const elementId = $(element).attr('id');
            const strToReplace = elementId.substr(elementId.lastIndexOf('_') + 1);
            const comparedElId = elementId.replace(strToReplace, options.field);
            const comparedValue = $('#' + comparedElId).val();

            if (!value || !comparedValue) {
                return true;
            }

            const firstDate = new Date(value);
            const secondDate = new Date(comparedValue);

            return secondDate >= firstDate;
        },
        function(param, element) {
            const value = String(this.elementValue(element));
            const placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.field = value;
            return __(param.message, placeholders);
        }
    ];
});
