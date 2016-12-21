define(function(require) {
    'use strict';

    var Select2AttendeesComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2AttendeesComponent = Select2AutocompleteComponent.extend({
        initialize: function(options) {
            Select2AttendeesComponent.__super__.initialize.call(this, options);
        },
        preConfig: function(config) {
            Select2AttendeesComponent.__super__.preConfig.call(this, config);
            config.maximumInputLength = 50;

            config.createSearchChoice = function(term, data) {
                var match = _.find(data, function(item) {
                    return item.displayName.toLowerCase().localeCompare(term.toLowerCase()) === 0;
                });
                if (typeof match === 'undefined') {
                    var emailPattern = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/ig;
                    var emails = term.match(emailPattern);
                    var email = emails ? emails.shift() : '';
                    var disallowSymbolsPattern = /[^a-zA-Z0-9\s._-]/ig;
                    var displayName = term.replace(emailPattern, '')
                        .replace(disallowSymbolsPattern, '')
                        .trim();
                    var text = ''; //it is used as text for autocomplete item
                    if (displayName) {
                        text = displayName + (email ? ' <' + email + '>' : '');
                    } else {
                        text = email;
                    }

                    return {
                        id: JSON.stringify({
                            displayName: displayName,
                            email: email,
                            status: 'none',
                            type: null
                        }),
                        text: text,
                        displayName: displayName,
                        email: email,
                        status: 'none',
                        type: null,
                        isNew: true
                    };
                }
                return null;
            };

            return config;
        },
        setConfig: function(config) {
            config.selected = config.selected || {};
            config = Select2AttendeesComponent.__super__.setConfig.apply(this, arguments);

            config.ajax.results = _.wrap(config.ajax.results, function(func, data, page) {
                var response = func.call(this, data, page);
                _.each(response.results, function(item) {
                    if (config.selected[item.id]) {
                        item.id = config.selected[item.id];
                    }
                });

                return response;
            });

            if (config.needsInit) {
                config.initSelection = function(element, callback) {
                    $.ajax({
                        url: routing.generate(
                            'oro_calendar_event_attendees_autocomplete_data',
                            {id: element.val()}
                        ),
                        type: 'GET',
                        success:  $.proxy(function(data) {
                            config.selected = data.excluded;
                            callback(data.result);
                            element.trigger('select2-data-loaded');
                        }, this)
                    });
                };
            }

            return config;
        }
    });

    return Select2AttendeesComponent;
});
