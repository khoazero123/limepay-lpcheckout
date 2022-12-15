define([
  "jquery",
  "underscore",
  "priceUtils"
], function ($, _, utils) {
    'use strict';

    return function (priceBox) {

        $.widget('mage.priceBox', $['mage']['priceBox'], {

            _create: function createLimepayWidget(event, prices) {
                var box = this.element;
                box.on('reloadPrice', this._updateInstalmentPrice.bind(this));

                this._super();
            },

            _updateInstalmentPrice() {
                if (this.element.parents('.product-info-price').length > 0 && this.element.parents('.page-main').length > 0) {
                    var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {};
                    _.each(this.cache.displayPrices, function (price, priceCode) {
                        price.final = _.reduce(price.adjustments, function (memo, amount) {
                            return memo + amount;
                        }, price.amount);

                        if (priceCode === 'finalPrice') {
                            var $lpAmtElem = $('#lp_installment_amt');
                            if ($lpAmtElem.length && !$lpAmtElem.closest('.limepay_installment_offer').hasClass('custom-defined-price')) {
                                var lpInstPrice = utils.formatPrice(this._GetInstallmentAmount(price.final), priceFormat);
                                $lpAmtElem.text(lpInstPrice);
                            }
                        }

                    }, this);
                }
            },

            _GetInstallmentAmount($amount)
            {
                return $amount/4;
            }

        });
        return $['mage']['priceBox'];
    }
});
