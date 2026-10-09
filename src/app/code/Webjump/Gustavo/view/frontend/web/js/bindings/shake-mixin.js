define([
    'Webjump_Gustavo/js/bindings/shake'
], function (shake) {
    'use strict';

    return function (target) {
        target.shake = shake;
        return target;
    };
});
