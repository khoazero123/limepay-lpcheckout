define([
  'Magento_Checkout/js/model/quote'
], function (quote) {
    'use strict';

    return function (component) {
        quote.setOSC('aheadworks');
        return component;
    }
});
