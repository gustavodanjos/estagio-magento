define([
    'halloween-fx/bats',
    'halloween-fx/scare'
], function (bats, scare) {
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
        var enabled = isMotionAllowed() && !isTransactional();

        bats.init({ enabled: enabled });
        scare.init({ enabled: enabled });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    return { start: start };
});