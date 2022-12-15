/*jshint browser:true jquery:true*/
/*global alert*/
var config = {
    map: {
      '*': {
          'lpcheckoutjs': 'https://checkout-v3.limepay.com.au/v3/checkout-v3.0.0.min.js',
          'limepay_payments': 'Limepay_Lpcheckout/js/limepay_payments'
      }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'Limepay_Lpcheckout/js/widgets/price-update-handler': true
            },
            'Magento_Checkout/js/model/quote': {
                'Limepay_Lpcheckout/js/model/quote-mixin': true
            },
            'Magento_Ui/js/view/messages': {
                'Limepay_Lpcheckout/js/messages-mixin': true
            },
            'Aheadworks_OneStepCheckout/js/view/html-content': {
                'Limepay_Lpcheckout/js/view/aheadworks-osc-mixin': true
            },
            'Aheadworks_OneStepCheckout/js/view/actions-toolbar/renderer/default': {
                'Limepay_Lpcheckout/js/view/aheadworks-actions-toolbar-mixin': true
            },
        }
    }
};
