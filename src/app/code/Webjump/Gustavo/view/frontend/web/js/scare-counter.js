define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, customerData, $t) {
    'use strict';

    var BADGE_CLASS = 'scare-counter-badge',
        BADGE_SELECTOR = '.' + BADGE_CLASS,
        TEXT_SELECTOR = '.' + BADGE_CLASS + '__text',
        DISPLAY_DURATION = 3000,
        currentCount = 0,
        hideTimer;

    function getBadge() {
        var $badge = $(BADGE_SELECTOR);

        if ($badge.length) {
            return $badge;
        }

        $badge = $(
            '<div class="' + BADGE_CLASS + '" role="status" aria-live="polite">' +
            '<span class="' + BADGE_CLASS + '__text"></span>' +
            '</div>'
        );
        $('body').append($badge);

        return $badge;
    }

    function displayBadge($badge) {
        window.clearTimeout(hideTimer);

        $badge.addClass(BADGE_CLASS + '--visible');

        hideTimer = window.setTimeout(function () {
            $badge.removeClass(BADGE_CLASS + '--visible');
        }, DISPLAY_DURATION);
    }

    return function () {
        customerData.get('scare-counter').subscribe(function (sectionData) {
            var count = sectionData ? parseInt(sectionData.count, 10) : 0;

            if (count === 0 || count <= currentCount) {
                return;
            }

            currentCount = count;
            getBadge().find(TEXT_SELECTOR)
                .text($t('Scared %1 times').replace('%1', count));
            displayBadge(getBadge());
        });
    };
});