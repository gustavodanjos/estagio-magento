/**
 * Live countdown to the end of the Noite Assombrada campaign.
 *
 * Receives its configuration from the template through x-magento-init, registers itself in
 * the uiRegistry so the `scope` binding of the markup can resolve it, and then updates an
 * observable every second. The message itself is a computed observable, so the DOM text is
 * refreshed by Knockout and never by a manual DOM write.
 */
define([
    'ko',
    'uiComponent',
    'uiRegistry'
], function (ko, Component, registry) {
    'use strict';

    var TICK_INTERVAL_MS = 1000,
        SECONDS_IN_MINUTE = 60,
        SECONDS_IN_HOUR = 3600,
        SECONDS_IN_DAY = 86400,
        MESSAGE_PLACEHOLDER = '%1';

    return Component.extend({
        defaults: {
            name: 'halloweenCountdown',
            targetTimestamp: 0,
            messageTemplate: '',
            conjunction: '',
            endedMessage: '',
            unitLabels: {}
        },

        /**
         * @param {Object} config Configuration coming from x-magento-init.
         */
        initialize: function (config) {
            this._super();

            this.timerId = null;
            this.targetTimestamp = Number(this.targetTimestamp || 0) * 1000;

            this.observe({
                now: Date.now()
            });
            this.remainingSeconds = ko.computed(this._getRemainingSeconds, this);
            this.isEnded = ko.computed(this._getIsEnded, this);
            this.message = ko.computed(this._getMessage, this);

            registry.set(this.name, this);

            this.startTimer();
        },

        startTimer: function () {
            this.timerId = setInterval(this._tick.bind(this), TICK_INTERVAL_MS);
        },

        destroy: function (skipUpdate) {
            this.stopTimer();

            this._super(skipUpdate);
        },

        stopTimer: function () {
            if (this.timerId) {
                clearInterval(this.timerId);
                this.timerId = null;
            }
        },

        _tick: function () {
            this.now(Date.now());
        },

        _getRemainingSeconds: function () {
            var remaining = Math.floor((this.targetTimestamp - this.now()) / 1000);

            return remaining > 0 ? remaining : 0;
        },

        _getIsEnded: function () {
            return this.remainingSeconds() === 0;
        },

        _getMessage: function () {
            if (this.isEnded()) {
                return this.endedMessage;
            }

            return this._formatMessage(this.remainingSeconds());
        },

        _formatMessage: function (totalSeconds) {
            var units = [
                this._formatUnit('day', Math.floor(totalSeconds / SECONDS_IN_DAY)),
                this._formatUnit('hour', Math.floor(totalSeconds % SECONDS_IN_DAY / SECONDS_IN_HOUR)),
                this._formatUnit('minute', Math.floor(totalSeconds % SECONDS_IN_HOUR / SECONDS_IN_MINUTE)),
                this._formatUnit('second', totalSeconds % SECONDS_IN_MINUTE)
            ];

            return this.messageTemplate.replace(
                MESSAGE_PLACEHOLDER,
                units.slice(0, -1).join(', ') + ' ' + this.conjunction + ' ' + units[units.length - 1]
            );
        },

        _formatUnit: function (unit, count) {
            var labels = this.unitLabels[unit] || {},
                label = count === 1 ? labels.singular : labels.plural;

            return count + ' ' + (label || unit);
        }
    });
});
