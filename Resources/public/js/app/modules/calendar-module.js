define(function(require) {
    'use strict';

    const $ = require('jquery.validate');

    $.validator.loadMethod('orocalendar/js/validator/dateearlierthan');
});
