define([
    'ko'
], function (ko) {
    'use strict';

    var INTENSITY_PRESETS = {
        low: { distance: '2px', rotation: '0.5deg' },
        medium: { distance: '5px', rotation: '1.5deg' },
        high: { distance: '10px', rotation: '3deg' }
    };

    var DEFAULT_DURATION_MS = 500;
    var ACTIVE_CLASS = 'ko-shake-active';

    function parseIntensity(intensity) {
        if (!intensity) {
            return INTENSITY_PRESETS.medium;
        }

        if (typeof intensity === 'string' && INTENSITY_PRESETS[intensity.toLowerCase()]) {
            return INTENSITY_PRESETS[intensity.toLowerCase()];
        }

        var numeric = parseFloat(intensity);
        if (isNaN(numeric) || numeric <= 0) {
            return INTENSITY_PRESETS.medium;
        }

        var distancePx = numeric + 'px';
        var rotationDeg = Math.min(Math.max(numeric * 0.3, 0.5), 5).toFixed(1) + 'deg';

        return {
            distance: distancePx,
            rotation: rotationDeg
        };
    }

    function parseDuration(duration) {
        if (!duration) {
            return {
                cssValue: DEFAULT_DURATION_MS + 'ms',
                ms: DEFAULT_DURATION_MS
            };
        }

        if (typeof duration === 'number') {
            return {
                cssValue: duration + 'ms',
                ms: duration
            };
        }

        var durationStr = String(duration).trim();
        if (durationStr.indexOf('ms') !== -1) {
            var ms = parseFloat(durationStr) || DEFAULT_DURATION_MS;
            return { cssValue: ms + 'ms', ms: ms };
        }

        if (durationStr.indexOf('s') !== -1) {
            var seconds = parseFloat(durationStr) || (DEFAULT_DURATION_MS / 1000);
            return { cssValue: seconds + 's', ms: seconds * 1000 };
        }

        var parsed = parseFloat(durationStr);
        if (!isNaN(parsed) && parsed > 0) {
            return { cssValue: parsed + 'ms', ms: parsed };
        }

        return {
            cssValue: DEFAULT_DURATION_MS + 'ms',
            ms: DEFAULT_DURATION_MS
        };
    }

    function extractOptions(value) {
        if (!value) {
            return {
                enabled: false,
                intensity: INTENSITY_PRESETS.medium,
                duration: parseDuration(DEFAULT_DURATION_MS),
                trigger: 'auto'
            };
        }

        if (value === true) {
            return {
                enabled: true,
                intensity: INTENSITY_PRESETS.medium,
                duration: parseDuration(DEFAULT_DURATION_MS),
                trigger: 'auto'
            };
        }

        if (typeof value === 'object') {
            return {
                enabled: value.enabled !== false,
                intensity: parseIntensity(value.intensity),
                duration: parseDuration(value.duration),
                trigger: value.trigger || 'auto'
            };
        }

        return {
            enabled: true,
            intensity: parseIntensity(value),
            duration: parseDuration(DEFAULT_DURATION_MS),
            trigger: 'auto'
        };
    }

    function applyCssVariables(element, options) {
        element.style.setProperty('--ko-shake-distance', options.intensity.distance);
        element.style.setProperty('--ko-shake-distance-neg', '-' + options.intensity.distance);
        element.style.setProperty('--ko-shake-rotation', options.intensity.rotation);
        element.style.setProperty('--ko-shake-rotation-neg', '-' + options.intensity.rotation);
        element.style.setProperty('--ko-shake-duration', options.duration.cssValue);

        if (options.trigger === 'loop' || options.trigger === 'infinite') {
            element.style.setProperty('--ko-shake-iteration', 'infinite');
        } else {
            element.style.setProperty('--ko-shake-iteration', '1');
        }

        if (window.getComputedStyle(element).display === 'inline') {
            element.style.display = 'inline-block';
        }
    }

    function triggerShake(element, durationMs) {
        element.classList.remove(ACTIVE_CLASS);
        // Force reflow so repeated shakes restart cleanly
        void element.offsetWidth;
        element.classList.add(ACTIVE_CLASS);

        if (element._koShakeTimeout) {
            clearTimeout(element._koShakeTimeout);
        }

        element._koShakeTimeout = setTimeout(function () {
            element.classList.remove(ACTIVE_CLASS);
            element._koShakeTimeout = null;
        }, durationMs + 50);
    }

    ko.bindingHandlers.shake = {
        init: function (element, valueAccessor) {
            var rawValue = ko.unwrap(valueAccessor());
            var options = extractOptions(rawValue);

            applyCssVariables(element, options);

            element._koShakeHandlers = {};

            if (options.trigger === 'hover') {
                element._koShakeHandlers.mouseenter = function () {
                    var currentOptions = extractOptions(ko.unwrap(valueAccessor()));
                    applyCssVariables(element, currentOptions);
                    triggerShake(element, currentOptions.duration.ms);
                };
                element.addEventListener('mouseenter', element._koShakeHandlers.mouseenter);
            } else if (options.trigger === 'click') {
                element._koShakeHandlers.click = function () {
                    var currentOptions = extractOptions(ko.unwrap(valueAccessor()));
                    applyCssVariables(element, currentOptions);
                    triggerShake(element, currentOptions.duration.ms);
                };
                element.addEventListener('click', element._koShakeHandlers.click);
            } else if (options.trigger === 'loop' || options.trigger === 'infinite') {
                element.classList.add(ACTIVE_CLASS);
            } else if (options.enabled) {
                triggerShake(element, options.duration.ms);
            }

            ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
                if (element._koShakeTimeout) {
                    clearTimeout(element._koShakeTimeout);
                    element._koShakeTimeout = null;
                }
                if (element._koShakeHandlers) {
                    if (element._koShakeHandlers.mouseenter) {
                        element.removeEventListener('mouseenter', element._koShakeHandlers.mouseenter);
                    }
                    if (element._koShakeHandlers.click) {
                        element.removeEventListener('click', element._koShakeHandlers.click);
                    }
                    element._koShakeHandlers = null;
                }
            });
        },

        update: function (element, valueAccessor) {
            var rawValue = ko.unwrap(valueAccessor());
            var options = extractOptions(rawValue);

            applyCssVariables(element, options);

            if (!options.enabled) {
                element.classList.remove(ACTIVE_CLASS);
                if (element._koShakeTimeout) {
                    clearTimeout(element._koShakeTimeout);
                    element._koShakeTimeout = null;
                }
                return;
            }

            if (options.trigger === 'loop' || options.trigger === 'infinite') {
                element.classList.add(ACTIVE_CLASS);
            } else if (options.trigger === 'auto') {
                triggerShake(element, options.duration.ms);
            }
        }
    };

    return ko.bindingHandlers.shake;
});
