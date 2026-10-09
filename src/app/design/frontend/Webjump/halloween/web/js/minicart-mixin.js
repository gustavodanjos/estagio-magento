define([
    'jquery',
    'ko',
    'mage/translate'
], function ($, ko) {
    'use strict';

    return function (Minicart) {
        return Minicart.extend({
            /**
             * @override
             */
            initialize: function () {
                this._super();

                this.hauntedMessage = ko.computed(function () {
                    var count = parseInt(this.getCartParam('summary_count'), 10) || 0;

                    if (count === 0) {
                        return $.mage.__('Your casket is empty... for now.');
                    }

                    if (count === 1) {
                        return $.mage.__('One relic haunts your casket.');
                    }

                    if (count < 5) {
                        return $.mage.__('%1 relics haunt your casket.').replace('%1', count);
                    }

                    return $.mage.__('Your casket overflows with %1 relics!').replace('%1', count);
                }, this);

                return this;
            }
        });
    };
});
