define([], function () {
    'use strict';

    var BAT_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 24" width="48" height="24" ' +
        'fill="currentColor" aria-hidden="true">' +
        '<path d="M24 9.5c-2.6 0-4.4 1.9-4.4 4.3 0 1.7 1.3 3 3 3.4l-.9 2.6h4.6l-.9-2.6c1.7-.4 3-1.7 3-3.4 0-2.4-1.8-4.3-4.4-4.3z"/>' +
        '<path d="M20.6 10.4C17 4.6 10.4 2.2 2.6 3.6c2.6 1.4 4.4 3.4 5.4 5.6-1.8-.7-3.6-.7-5 .1 2.3.7 3.9 2.2 4.6 4.1 1.5-1.3 3.4-1.8 5.6-1.4-.6 1.6-.5 3 .4 4.2 1.1-1.7 2.9-2.9 5.3-3.3z"/>' +
        '<path d="M27.4 10.4c3.6-5.8 10.2-8.2 18-6.8-2.6 1.4-4.4 3.4-5.4 5.6 1.8-.7 3.6-.7 5 .1-2.3.7-3.9 2.2-4.6 4.1-1.5-1.3-3.4-1.8-5.6-1.4.6 1.6.5 3-.4 4.2-1.1-1.7-2.9-2.9-5.3-3.3z"/>' +
        '</svg>',
        COUNT = 5,
        FLIGHT_DURATION = 900;

    function build() {
        var flock = document.createElement('div'),
            i,
            bat;

        flock.className = 'halloween-bats';
        flock.setAttribute('aria-hidden', 'true');

        for (i = 0; i < COUNT; i++) {
            bat = document.createElement('span');

            bat.className = 'halloween-bat';
            bat.style.setProperty('--delay', (i * 0.12).toFixed(2) + 's');
            bat.style.setProperty('--top', (6 + i * 7) + 'vh');
            bat.style.setProperty('--travel', (70 + i * 9) + 'vw');
            bat.style.setProperty('--scale', (0.6 + i * 0.13).toFixed(2));
            bat.innerHTML = BAT_SVG;
            flock.appendChild(bat);
        }

        document.body.appendChild(flock);

        setTimeout(function () {
            flock.remove();
        }, FLIGHT_DURATION + 600);
    }

    return {
        init: function (options) {
            if (!options.enabled) {
                return;
            }

            build();
        }
    };
});