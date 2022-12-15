define([
  'underscore',
  'Magento_Checkout/js/model/quote',
  'uiRegistry',
], function (_, quote, registry) {
    'use strict';

    return function (component) {
        return component.extend({
            // Overriding Aheadworks/OneStepCheckout/view/frontend/web/js/view/actions-toolbar/renderer/default.js
            // to avoid error causing placeOrder method due to methodCode is not being set.
            initMethodsRenderComponent: function () {
                if (!this.methodCode && quote.paymentMethod()) {
                    this.methodCode = quote.paymentMethod().method;
                }
                if (this.methodCode) {
                    this.methodRendererComponent = registry.get('checkout.paymentMethod.methodList.' + this.methodCode);
                }

                return this;
            }
        });
    }
});
