window.FABNAMESPACE = window.FABNAMESPACE || 'FAB';
window[window.FABNAMESPACE] = window.window[window.FABNAMESPACE] || {};

function runAddFilter(F) {
    'use strict';

    if (! window.jQuery ||
        ! F.controller ||
        ! F.model
    ) {
        setTimeout(function() {
            runAddFilter(F);
        }, 10);
        return;
    }

    F.controller.make('AddFilter', {
        template: null,
        $controlsListItem: null,

        init: function() {
            let self = this;
            self.template = $('#JSFilterListItemTemplate').html();
            self.$controlsListItem = $('.JSFilterControlsListItem');
        },

        events:{
            click: function(e) {
                e.preventDefault();
                this.addFilter();
            }
        },

        addFilter: function() {
            let self = this;
            let $template;
            let template = self.template;

            $template = $(template.replace(
                RegExp('{{filterKey}}', 'g'),
                Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15)
            ));

            self.$controlsListItem.before($template);

            F.controller.construct('FilterLine', {
                el: $template
            });
        }
    });
}

runAddFilter(window[window.FABNAMESPACE]);

