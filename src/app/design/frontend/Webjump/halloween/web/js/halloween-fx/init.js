define([
    'halloween-fx/bats'
], function (bats) {
    'use strict';

    var REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)',
        TRANSACTIONAL_PATHS = ['/checkout', '/customer'];

    function isMotionAllowed() {
        return !window.matchMedia(REDUCED_MOTION_QUERY).matches;
    }

    function isTransactional() {
        return TRANSACTIONAL_PATHS.some(function (path) {
            return window.location.pathname.indexOf(path) === 0;
        });
    }

    function start() {
        bats.init({ enabled: isMotionAllowed() && !isTransactional() });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    return { start: start };
});