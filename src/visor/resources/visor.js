// Make sure FAB is defined
window.FABNAMESPACE = window.FABNAMESPACE || 'FAB';
window[window.FABNAMESPACE] = window.window[window.FABNAMESPACE] || {};

function runMain(F) {
    'use strict';

    if (! window.jQuery ||
        ! F.controller ||
        ! F.model
    ) {
        setTimeout(function() {
            runMain(F);
        }, 10);
        return;
    }

    $('.JSAddFilter').each(function() {
        F.controller.construct('AddFilter', {
            el: this
        });
    });

    $('.JSFilterListItem').each(function() {
        F.controller.construct('FilterLine', {
            el: this
        });
    });
}

runMain(window[window.FABNAMESPACE]);
