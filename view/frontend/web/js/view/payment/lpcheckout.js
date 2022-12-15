define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'lpcheckout',
                component: 'Limepay_Lpcheckout/js/view/payment/method-renderer/lpcheckout-method'
            },
            {
                type: 'lpcheckout_payplan',
                component: 'Limepay_Lpcheckout/js/view/payment/method-renderer/lpcheckout-method'
            },
            {
                type: 'lpcheckout_paycard',
                component: 'Limepay_Lpcheckout/js/view/payment/method-renderer/lpcheckout-method'
            }
        );
        return Component.extend({});
    }
);
