window.FABNAMESPACE = window.FABNAMESPACE || 'FAB';
window[window.FABNAMESPACE] = window.window[window.FABNAMESPACE] || {};

function runFilterLine(F) {
    'use strict';

    if (! window.jQuery ||
        ! F.controller ||
        ! F.model
    ) {
        setTimeout(function() {
            runFilterLine(F);
        }, 10);
        return;
    }

    F.controller.make('FilterLine', {
        $channelFilterValues: null,
        $otherFilterValues: null,
        $operator: null,
        $operatorIs: null,
        $operatorContains: null,

        init: function() {
            let self = this;

            self.$channelFilterValues = self.$el.find('.JSChannelFilterValues');
            self.$otherFilterValues = self.$el.find('.JSOtherFilterValues');
            self.$operator = self.$el.find('.JSOperator');
            self.$operatorIs = self.$el.find('.JSOperatorIs');
            self.$operatorContains = self.$el.find('.JSOperatorContains');
        },

        events: {
            'change .JSFilterType': function(e) {
                this.filterTypeRespond(e.currentTarget.value);
            },
            'change .JSOperator': function() {
                this.$el.find('.JSPendingWrapper').addClass('filters__pending-wrapper');
            },
            'change .JSChannelFilterValues': function() {
                this.$el.find('.JSPendingWrapper').addClass('filters__pending-wrapper');
            },
            'change .JSOtherFilterValues': function() {
                this.$el.find('.JSPendingWrapper').addClass('filters__pending-wrapper');
            },
            'click .JSRemoveFilter': function(e) {
                e.preventDefault();
                this.removeFilter($(e.currentTarget));
            }
        },

        filterTypeRespond: function(val) {
            let self = this;

            self.$el.find('.JSPendingWrapper').addClass('filters__pending-wrapper');

            if (! val) {
                self.$operator.hide();
                self.$channelFilterValues.hide().attr('name', '');
                self.$otherFilterValues.hide().attr('name', '');
                return;
            }

            self.$operator.show();

            if (val === 'channel') {
                self.$channelFilterValues.show()
                    .attr('name', self.$channelFilterValues.data('name'));
                self.$otherFilterValues.hide().attr('name', '');
                self.$operator.val('is');
                self.$operatorContains.prop('disabled', true);
                return;
            }


            self.$operatorContains.prop('disabled', false);

            self.$channelFilterValues.hide().attr('name', '');
            self.$otherFilterValues.show()
                .attr('name', self.$otherFilterValues.data('name'));
        },

        removeFilter: function($button) {
            let $filterParent = $button.closest('.JSFilterListItem');

            if ($filterParent.data('isNew')) {
                $filterParent.remove();
                return;
            }

            $filterParent.html(
                '<div class="filters__pending-wrapper JSPendingWrapper">Filter pending deletion</div>'
            );
        }
    });
}

runFilterLine(window[window.FABNAMESPACE]);

