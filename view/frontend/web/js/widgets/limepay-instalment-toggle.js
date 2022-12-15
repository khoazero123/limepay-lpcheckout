define([
    'jquery',
    'jquery/jquery.cookie'
], function ($) {

    $.widget('limepay.installmentToggle', {
        /**
         * This method constructs a new widget.
         * @private
         */
        _create: function () {
            var self = this;

            /* Trigger checkbox on click of whole area */
            $(document).on('click', '.lp-toggle-container', function(e) {
                var toggleElem = $(this).find('input[type="checkbox"]');
                toggleElem.prop('checked', !toggleElem.prop("checked"));
                self._toggleChange(toggleElem);
            });
        },

        _toggleChange: function(toggleElem) {
            var self = this;
            var minAmtLimit = parseFloat(self._accessCookie('minAllowedAmt'));

            if (toggleElem.closest('.lp-toggle-container').hasClass('payplan-disabled')) {
                toggleElem.prop('checked', false);
                var parentLabelElem = toggleElem.closest('.switch');
                parentLabelElem.addClass("disabled-swt");
                // Reset Toggle Animations;
                setTimeout(function() {
                    parentLabelElem.removeClass("disabled-swt");
                }, 610);
                // Add popover;
                if ($(".lp-popover").length == 0) {
                    $(".lp-toggle-container").after('<div class="lp-popover-bottom lp-popover"><div class="arrow"></div><div class="popover-body">Split the cost over 4 payments when you spend $' + minAmtLimit +' or more</div></div>');
                }
                return false;
            }
            
            
            if (toggleElem.prop('checked')) {
                self._createCookie('lpInstallmentToken', '1', 120); /* Set cookie for 2 hours */
                $('.lp-toggle-container .payment-type').removeClass('active');
                $('.lp-toggle-container .payment-type.lp-split-payment').addClass('active');
                $('.limepay-installment-offer__shortcode .limepay-installment-price').addClass('active');
            } else {
                self._deleteCookie('lpInstallmentToken');
                $('.lp-toggle-container .payment-type').removeClass('active');
                $('.limepay-installment-offer__shortcode .limepay-installment-price').removeClass('active')
                $('.lp-toggle-container .payment-type.lp-one-time').addClass('active');
            }
        },

        _createCookie: function (cookieName, value, minutes) {
            var date = new Date();
            date.setTime(date.getTime() + (minutes * 60 * 1000));
        
            $.cookie(cookieName, value, {expires: date});
        },

        _accessCookie: function (cookieName) {
            return $.cookie(cookieName);
        },

        _deleteCookie: function (cookieName) {
            $.cookie(cookieName, '', {path: '/', expires: -1});
        }
    });

    return $.limepay.installmentToggle;
});
