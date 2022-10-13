define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/tools',
    'oroui/js/error'
], function($, _, __, tools, error) {
    'use strict';

    return {
        /**
         * Shows errors represent by the given err object.
         *
         * @param container {jQuery|string} The jQuery object or jQuery selector for a element contains form fields.
         *                                  It may be form element itself or any element contains the form
         * @param err {string|Object} Can be a string, exception object or an object represents JSON REST response
         * @param formFieldPrefix {string} [optional] A prefix of form field id. If it is not specified the form id
         *                                  will be used as the prefix.
         */
        handleErrors: function(container, err, formFieldPrefix) {
            if (_.isString(container)) {
                container = $(container);
            }
            this.removeErrors(container);
            const errors = [];
            if (tools.debug) {
                error.showErrorInConsole(err);
            }
            if (_.isString(err)) {
                // string
                errors.push(err);
            }
            if (!_.isUndefined(err.message)) {
                // exception object
                errors.push(err.message);
            } else {
                errors.push(__('Unexpected error was occurred'));
            }
            if (!_.isUndefined(err.errors)) {
                // JSON REST response
                if (_.isArray(err.errors)) {
                    _.each(err.errors, function(value) {
                        errors.push(value);
                    });
                } else if (_.isObject(err.errors)) {
                    const from = container.is('form') ? container : container.find('form');
                    const validator = from.data('validator');
                    if (validator) {
                        validator.showBackendErrors(err.errors, from.attr('name'));
                    }
                }
            } else {
                this.addErrors(container, errors);
            }

            if (!_.isUndefined(err.children)) {
                // JSON REST response
                if (_.isUndefined(formFieldPrefix)) {
                    formFieldPrefix = this.getFormFieldPrefix(container);
                }
                _.each(err.children,
                    (value, key) => {
                        const field = container.find('#' + formFieldPrefix + key);
                        this.removeFieldErrors(field);

                        if (!_.isUndefined(value.errors) && _.isArray(value.errors)) {
                            this.addFieldErrors(field, value.errors);
                        }
                    });
                this.setFocusOnFirstErrorField(container);
            }
        },

        /**
         * Gets a prefix of form field id. Usually it equal to form id ends with underscore character (_).
         *
         * @param container {jQuery|string} The jQuery object or jQuery selector for a element contains form fields.
         *                                  It may be form element itself or any element contains the form
         */
        getFormFieldPrefix: function(container) {
            let formFieldPrefix = '';
            let form = null;
            if (container.prop('tagName').toLowerCase() === 'form') {
                form = container;
            } else {
                form = container.find('form');
            }
            if (!_.isNull(form)) {
                formFieldPrefix = form.attr('id');
                if (!_.isUndefined(formFieldPrefix)) {
                    formFieldPrefix += '_';
                } else {
                    formFieldPrefix = '';
                }
            }
            return formFieldPrefix;
        },

        /**
         * Removes all field level errors.
         *
         * @param field {jQuery|string} The jQuery object or jQuery selector for a form field element.
         */
        removeFieldErrors: function(field) {
            const $field = $(field);
            const $container = $field.closest('.controls');

            $container
                .removeClass('validation-error')
                .find('.error')
                .removeClass('error');
        },

        /**
         * Adds field level errors.
         *
         * @param field {jQuery|string} The jQuery object or jQuery selector for a form field element.
         * @param errorMessages {string[]|string} The localized error string(s).
         */
        addFieldErrors: function(field, errorMessages) {
            let $field = $(field);
            const $container = $field.closest('div.controls');

            if (!$field.is(':visible')) {
                $field = $container.children(':input');
            }

            let $errorContainer = $field.siblings('.validation-failed');

            if (!$errorContainer.length) {
                $errorContainer = $('<span class="validation-failed"></span>');
                $field.after($errorContainer);
            }

            $errorContainer.show().text(_.isArray(errorMessages) ? errorMessages.join('; ') : errorMessages);
            $field.addClass('error');
            $container.addClass('validation-error');
        },

        /**
         * Removes all form level errors.
         *
         * @param container {jQuery|string} The jQuery object or jQuery selector for a element contains form fields.
         *                                  It may be form element itself or any element contains the form
         */
        removeErrors: function(container) {
            if (_.isString(container)) {
                container = $(container);
            }
            const errorContainer = container.find('.alert-error');
            if (errorContainer.length > 0) {
                errorContainer.hide();
                const errorList = errorContainer.find('ul');
                errorList.empty();
            }
        },

        /**
         * Adds form level errors.
         *
         * @param container {jQuery|string} The jQuery object or jQuery selector for a element contains form fields.
         *                                  It may be form element itself or any element contains the form
         * @param errorMessages {string[]|string} The localized error string(s).
         */
        addErrors: function(container, errorMessages) {
            if (_.isString(container)) {
                container = $(container);
            }
            const errorContainer = container.find('.alert-error');
            if (errorContainer.length > 0) {
                let errorList = errorContainer.find('ul');
                if (_.size(errorMessages) > 0) {
                    if (errorList.length === 0) {
                        errorList = $('<ul>');
                        errorContainer.append(errorList);
                    }
                    _.each(errorMessages,
                        function(value) {
                            this.append($('<li>').text(value));
                        },
                        errorList);
                    errorContainer.show();
                }
            }
        },

        /**
         * Sets the focus to the first erroneous form field.
         *
         * @param container {jQuery|string} The jQuery object or jQuery selector for a element contains form fields.
         *                                  It may be form element itself or any element contains the form
         */
        setFocusOnFirstErrorField: function(container) {
            if (_.isString(container)) {
                container = $(container);
            }
            container.find('.error>:input,:input.error').first().focus();
        }
    };
});
