/*----------------------------------------------------------------------------*\
    # Copyright 2017, BuzzingPixel, LLC

    # This program is free software: you can redistribute it and/or modify
    # it under the terms of the Apache License 2.0.
    # http://www.apache.org/licenses/LICENSE-2.0
\*----------------------------------------------------------------------------*/

// Make sure FAB is defined
window.FABNAMESPACE = window.FABNAMESPACE || 'FAB';
window[window.FABNAMESPACE] = window.window[window.FABNAMESPACE] || {};

// Set up fab controller
function runFabController(F) {
    'use strict';

    if (window.jQuery === undefined) {
        setTimeout(function() {
            runFabController(F);
        }, 10);
        return;
    }

    var jQuery = window.jQuery;

    // A place to store constructors
    var constructors = {};

    // A place to store constructed objects
    var constructed = [];

    /**
     * Make a controller constructor
     *
     * @param {String|Object} fn - Name of constructor or options to return constructor only
     * @param {Object} obj - Options
     * @return {constructor}
     */
    var createConstructor = function(fn, obj) {
        // Constructor variable
        var constructor;

        // Determine type of first argument
        if (typeof fn === 'object') {
            obj = fn;
            fn = null;
        } else if (typeof fn !== 'string') {
            obj = {};
            fn = null;
        }

        /**
         * Create a constructor
         *
         * @param {Object|boolean|null} optObj
         */
        constructor = function(optObj) {
            // Set a variable for this
            var thisObj = this;

            // Clone the object
            var newObj = $.extend(true, {}, obj);

            // Set iterator key
            var i;

            // Set model var
            var ControllerModel;

            // Loop through the options and set them
            for (i in newObj) {
                if (newObj.hasOwnProperty(i)) {
                    thisObj[i] = newObj[i];
                }
            }

            // Loop through incoming options and set them
            if (typeof optObj === 'object') {
                for (i in optObj) {
                    if (optObj.hasOwnProperty(i)) {
                        thisObj[i] = optObj[i];
                    }
                }
            }

            // Check if el or $el has been set
            if (! thisObj.el) {
                thisObj.$el = $('<div></div>');
                thisObj.el = thisObj.$el.get(0);

                // If the incoming el is a string, use as selector
            } else if (typeof thisObj.el === 'string') {
                thisObj.$el = $(thisObj.el);
                thisObj.el = thisObj.$el.get(0);

                // Check if the incoming el is a jQuery object
            } else if (thisObj.el instanceof jQuery) {
                thisObj.$el = thisObj.el;
                thisObj.el = thisObj.$el.get(0);

                // Check if the incoming el is a DOM object
            } else if (thisObj.el instanceof HTMLElement) {
                thisObj.$el = $(thisObj.el);
            }

            // Check if model is set
            if (typeof thisObj.model === 'object' && typeof F.model !== 'undefined') {
                if (! F.model.validateGuid(thisObj.model.guid)) {
                    ControllerModel = F.model.make(thisObj.model);
                    thisObj.model = new ControllerModel();
                }
            } else if (this.model !== undefined) {
                thisObj.model = null;
            }

            // Check for init
            if (typeof thisObj.init === 'function') {
                // Remove the first argument from the arguments
                Array.prototype.shift.apply(arguments);

                // Run the init function
                thisObj.init.apply(thisObj, arguments);
            }

            // Check if there are events
            if (typeof thisObj.events === 'object') {
                // Loop through the events
                for (i in thisObj.events) {
                    if (
                        // Make sure the object has own property
                        thisObj.events.hasOwnProperty(i) &&
                        // Make sure the value is a callable function
                        typeof thisObj.events[i] === 'function'
                    ) {
                        // Enclose in a function so we have immutable vars
                        (function(key, callback) {
                            if (key[1]) {
                                thisObj.$el.on(key[0], key[1], function() {
                                    callback.apply(thisObj, arguments);
                                });
                            } else {
                                thisObj.$el.on(key[0], function() {
                                    callback.apply(thisObj, arguments);
                                });
                            }
                        })(i.split(' '), thisObj.events[i]);
                    }
                }
            }
        };

        // If a name has been defined for this constructor, add it
        if (fn) {
            constructors[fn] = constructor;
        }

        // Return the constructor
        return constructor;
    };

    /**
     * Controller API
     */
    F.controller = {
        /**
         * Make a controller constructor
         * @return {constructor}
         */
        make: function() {
            return createConstructor.apply(createConstructor, arguments);
        },

        /**
         * Construct a new object from a constructor
         *
         * @param {String} name
         */
        construct: function(name) {
            // Set a variable for the constructed object
            var constructedObj;

            // Make sure the constructor exists
            if (typeof constructors[name] !== 'function') {
                return null;
            }

            // Run the constructor
            constructedObj = new (Function.prototype.bind.apply(
                    constructors[name], arguments)
            )();

            // Push the constructor into the array of constructed objects
            constructed.push(constructedObj);

            // Return the constructed object
            return constructedObj;
        }
    };
}

// Run fab controller set up
runFabController(window[window.FABNAMESPACE]);
