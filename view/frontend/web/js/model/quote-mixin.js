define([
    'underscore'
], function (_) {
    'use strict';

    return function (quote) {
        return _.extend(quote, {
            osc: false,

            setOSC: function (osc) {
                this.osc = osc;
            },
            getOSC: function () {
                return this.osc;
            }
        });
    }
});
