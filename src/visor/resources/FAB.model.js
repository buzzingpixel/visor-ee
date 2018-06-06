/*----------------------------------------------------------------------------*\
    # Copyright 2017, BuzzingPixel, LLC

    # This program is free software: you can redistribute it and/or modify
    # it under the terms of the Apache License 2.0.
    # http://www.apache.org/licenses/LICENSE-2.0
\*----------------------------------------------------------------------------*/

// Make sure FAB is defined
window.FABNAMESPACE = window.FABNAMESPACE || 'FAB';
window[window.FABNAMESPACE] = window.window[window.FABNAMESPACE] || {};

(function(F) {
    'use strict';

    // A place to store GUIDs
    var guids = [];

    // Create GUID
    var guid = function() {
        var s4 = function() {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        };

        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
    };

    /**
     * Make a model constructor
     *
     * @param {Object} attrs
     */
    var createModelConstructor = function(attrs) {
        // Set a variable for the constructor
        var constructor;

        // Make sure attrs is an object
        if (typeof attrs !== 'object') {
            attrs = {};
        }

        // Define the constructor
        constructor = function(initialValues) {
            // Set a local variable for this
            var thisObj = this;

            // Set GUID
            thisObj.guid = guid();

            // Push the GUID
            guids.push(thisObj.guid);

            // Set up properties
            thisObj.properties = {};

            // Set up property types
            thisObj.propertyTypes = attrs;

            // Set up change events
            thisObj.changeEvents = {};

            // Loop through property types and set change events objects
            for (var prop in thisObj.propertyTypes) {
                if (thisObj.propertyTypes.hasOwnProperty(prop)) {
                    thisObj.changeEvents[prop] = {};
                }
            }

            // Set any initial values
            for (var i in initialValues) {
                if (initialValues.hasOwnProperty(i)) {
                    if (thisObj.propertyTypes[i] !== undefined) {
                        thisObj.properties[i] = initialValues[i];
                    }
                }
            }
        };

        // Set prototype methods
        constructor.prototype = {
            get: function(name) {
                var model = this;

                if (model.properties[name]) {
                    if (model.propertyTypes[name] === 'int') {
                        return parseInt(model.properties[name]);
                    } else if (model.propertyTypes[name] === 'float') {
                        return parseFloat(model.properties[name]);
                    } else if (model.propertyTypes[name] === 'string') {
                        return String(model.properties[name]);
                    } else if (model.propertyTypes[name] === 'bool') {
                        return model.properties[name] === true;
                    } else if (model.propertyTypes[name] === 'array') {
                        if (model.properties[name].constructor === Array) {
                            return model.properties[name];
                        } else {
                            return [];
                        }
                    } else if (model.propertyTypes[name] === 'object') {
                        if (model.properties[name].constructor === Object) {
                            return model.properties[name];
                        } else {
                            return {};
                        }
                    } else {
                        return null;
                    }
                } else {
                    if (
                        model.propertyTypes[name] === 'int' ||
                        model.propertyTypes[name] === 'float'
                    ) {
                        return 0;
                    } else if (model.propertyTypes[name] === 'string') {
                        return '';
                    } else if (model.propertyTypes[name] === 'bool') {
                        return false;
                    } else if (model.propertyTypes[name] === 'array') {
                        return [];
                    } else if (model.propertyTypes[name] === 'object') {
                        return {};
                    } else {
                        return null;
                    }
                }
            },
            set: function(name, val) {
                var model = this;
                var changed = false;
                var oldVal = model.properties[name];
                var args;

                if (model.propertyTypes[name] === 'int') {
                    model.properties[name] = parseInt(val);
                    changed = true;
                } else if (model.propertyTypes[name] === 'float') {
                    model.properties[name] = parseFloat(val);
                    changed = true;
                } else if (model.propertyTypes[name] === 'string') {
                    model.properties[name] = String(val);
                    changed = true;
                } else if (model.propertyTypes[name] === 'bool') {
                    model.properties[name] = val === true || val === 'true' || val === 'yes' || val === 'y';
                    changed = true;
                } else if (model.propertyTypes[name] === 'array') {
                    if (val.constructor === Array) {
                        model.properties[name] = val;
                        changed = true;
                    }
                } else if (model.propertyTypes[name] === 'object') {
                    if (val.constructor === Object) {
                        model.properties[name] = val;
                        changed = true;
                    }
                }

                if (! changed || oldVal === model.properties[name] || model.changeEvents[name] === undefined) {
                    return;
                }

                args = [
                    model.properties[name],
                    oldVal
                ];

                for (var i in model.changeEvents[name]) {
                    model.changeEvents[name][i].forEach(function(callback) {
                        callback.apply(callback, args);
                    });
                }
            },
            onChange: function(name, callback) {
                var model = this;
                var key = name.split('.');

                if (model.propertyTypes[key[0]] !== undefined) {
                    if (key[1] === undefined) {
                        if (model.changeEvents[key[0]].noNameSpace === undefined) {
                            model.changeEvents[key[0]].noNameSpace = [];
                        }

                        model.changeEvents[key[0]].noNameSpace.push(callback);
                    } else {
                        if (model.changeEvents[key[0]][key[1]] === undefined) {
                            model.changeEvents[key[0]][key[1]] = [];
                        }

                        model.changeEvents[key[0]][key[1]].push(callback);
                    }
                }
            },
            offChange: function(name) {
                var model = this;
                var key = name.split('.');

                if (key[1] === undefined) {
                    model.changeEvents[key[0]] = {};
                } else {
                    delete model.changeEvents[key[0]][key[1]];
                }
            }
        };

        // Return the constructor
        return constructor;
    };

    /**
     * Model API
     */
    F.model = {
        /**
         * Make a model constructor
         */
        make: function() {
            return createModelConstructor.apply(
                createModelConstructor,
                arguments
            );
        },

        /**
         * Validate GUID
         *
         * @param {String} guid
         */
        validateGuid: function(guid) {
            return guids.indexOf(guid) > -1;
        }
    };
})(window[window.FABNAMESPACE]);
