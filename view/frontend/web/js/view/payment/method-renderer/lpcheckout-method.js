define(
    [
        'jquery',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messages',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
    ],
    function (
        $,
        _,
        Component,
        selectPaymentMethodAction,
        checkoutData,
        quote,
        Messages,
        ko,
        fullScreenLoader,
        additionalValidators,
        redirectOnSuccessAction,
        limepayCheckout,
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Limepay_Lpcheckout/payment/lpcheckout',
                limepayPaymentToken: null,
                limepayPaymentActionObject: null,
                limepayCheckoutAddress: {},
                limepayCheckoutAmount: {},
            },
            isInAction: ko.observable(true),

            /**
             * @returns {exports.initialize}
             */
             initialize: function () {
                 this._super();
                 this.observeLimepayCheckoutParams();
                 this.updateLimepayCheckoutAddress(quote.billingAddress());
                 this.updateLimepayCheckoutAmount(quote.totals());

                 if (window.checkoutConfig.payment.lpcheckout.defaultPaymentMethod == '1') {
                    this.selectLimepayPaymentMethod();
                 }

                 return this;
             },

             /**
              * Initialize child elements
              *
              * @returns {Component} Chainable.
              */
             initChildren: function () {
                 this.messageContainer = new Messages();
                 this.createMessagesComponent();
                 return this;
             },

             /**
              * Initialize query parameters object with billing address.
              * This will help to use the data from their default address of a logged in customer
              *
              * @returns {Component} Chainable.
              */
             observeLimepayCheckoutParams: function() {
                console.log(quote.getOSC());
                quote.totals.subscribe(function () {
                   this.updateLimepayCheckoutAmount(quote.totals());
                }, this);

                quote.shippingAddress.subscribe(function () {
                    if (!this.shouldNotUseShippingAddressUpdates()) {
                        this.updateLimepayCheckoutAddress(quote.shippingAddress());
                    }
                }, this);

                quote.billingAddress.subscribe(function () {
                    if (!this.shouldNotUseBillingAddressUpdates()) {
                        this.updateLimepayCheckoutAddress(quote.billingAddress());
                    }
                }, this);

                return this;
             },

             updateLimepayCheckoutAddress: function(address) {
                var newAddress = _.pick(address, [
                  'firstname',
                  'middlename',
                  'lastname',
                  'street',
                  'city',
                  'region',
                  'postcode',
                  'countryId',
                  'telephone',
                ]);
                var sameAddress = _.isEqual(newAddress, this.limepayCheckoutAddress);
                this.limepayCheckoutAddress = newAddress;
                if (!sameAddress) {
                    this.renderLimepayCheckout();
                }
             },

             updateLimepayCheckoutAmount: function(amount) {
                 var newAMount = _.pick(amount, [
                   'base_grand_total',
                   'quote_currency_code'
                 ]);
                 var limepayCheckoutAmount = {
                    baseGrandTotal: Math.round(newAMount.base_grand_total * 100),
                    quoteCurrencyCode: newAMount.quote_currency_code
                 };
                 var sameAmount = _.isEqual(limepayCheckoutAmount, this.limepayCheckoutAmount);
                 this.limepayCheckoutAmount = limepayCheckoutAmount;
                 if (!sameAmount) {
                     this.renderLimepayCheckout();
                 }
             },

             getLimepayCheckoutAmount: function() {
                return {
                   currency: this.limepayCheckoutAmount.quoteCurrencyCode,
                   amount: this.limepayCheckoutAmount.baseGrandTotal
                };
             },

             getLimepayCheckoutAddress: function() {
                return this.limepayCheckoutAddress;
             },

             getResidentialAddress: function(address) {
                 var residAddress = (address.street ? address.street + ' ' : '');
                 residAddress += (address.city ? address.city + ' ' : '');
                 residAddress += (address.region ? address.region + ' ' : '');
                 residAddress += (address.postcode ? address.postcode + ' ' : '');
                 residAddress += (address.countryId ? address.countryId : '');

                 return residAddress;
             },

             showHideOptions: function() {
                 var hidePayLaterOption = false;
                 var hideFullPayOption = false;

                 if (this.getAvailablePaymentOption() === "paycard" || this.getCode() === "lpcheckout_paycard") {
                     hidePayLaterOption = true;
                 } else if (this.getAvailablePaymentOption() === "payplan" || this.getCode() === "lpcheckout_payplan") {
                     hideFullPayOption = true;
                 }
                 return {
                   hidePayLaterOption: hidePayLaterOption,
                   hideFullPayOption: hideFullPayOption,
                 }
             },

             renderLimepayCheckout: function() {
                 var self = this;

                 if (!self.limepayCheckout) {
                    return;
                 }

                 var email = (quote.guestEmail && quote.guestEmail !== 'mail@example.com') ? quote.guestEmail : window.checkoutConfig.customerData.email;
                 var publicKey = window.checkoutConfig.payment.lpcheckout.publishablekey;
                 var primaryColor = window.checkoutConfig.payment.lpcheckout.primaryColor;
                 var customToken = window.checkoutConfig.payment.lpcheckout.customToken;

                 var payOptions = self.showHideOptions();

                 var address = self.getLimepayCheckoutAddress();
                 var amount = self.getLimepayCheckoutAmount();

                 var initParams = {
                   publicKey: publicKey,
                   email: email,
                   customerFirstName: address.firstname,
                   customerMiddleName: address.middlename,
                   customerLastName: address.lastname,
                   customerResidentialAddress: self.getResidentialAddress(address),
                   phone: address.telephone,
                   hidePayLaterOption: payOptions.hidePayLaterOption,
                   hideFullPayOption: payOptions.hideFullPayOption,
                   paymentToken: self.handlePaymentToken.bind(self),
                   platform: 'magento',
                   platformVersion: '2',
                   platformPluginVersion: limepay.version,
                   customerToken: customToken
                 };

                 var renderParams = {
                     elementId: self.getPlaceHolderId(),
                     currency: amount.currency,
                     amount: amount.amount,
                     paymentType: self.getCode() == "lpcheckout" && self.isPayplanOptionPreselected() ? 'payplan' : 'paycard',
                     showPayNow: false,
                     showPayPlanSubmit: false,
                 };
                 if (primaryColor) {
                    renderParams.primaryColor = primaryColor;
                 }

                 self.limepayCheckout.init(initParams);
                 self.limepayCheckout.render(renderParams);
                 self.initLimepayEventHandlers();
             },

            /**
            * In OSCs where billing address is placed after payment options,
            * iframe url should not be updated when user change/update/add their billing address
            *
            * 1. Swissup_Firecheckout OSC
            *
            * @returns {Boolean}.
            */
            shouldNotUseBillingAddressUpdates: function() {
                if (window.checkoutConfig.isFirecheckout || _.has(window.checkoutConfig, 'bssOsc') || quote.getOSC() === 'aheadworks') {
                   return true;
                }
                return false;
            },

            shouldNotUseShippingAddressUpdates: function() {

                if (_.has(window.checkoutConfig, 'bssOsc') || quote.getOSC() === 'aheadworks' || quote.isVirtual()) {
                   return true;
                }
                return false;
            },

            /**
             * Select payment method based on
             *  1. selectedPaymentOption - passed in configurations calculated based on sort_order
             *  2. isPayplanOptionPreselected - whether customer switched to BNPL in a widget
             */
            selectLimepayPaymentMethod: function () {
                var selectedPaymentOption = window.checkoutConfig.payment.lpcheckout.selectedPaymentOption;
                var code = this.getCode();
                if ((code == "lpcheckout" && code === selectedPaymentOption) ||
                  (code == "lpcheckout_payplan" && (code === selectedPaymentOption || this.isPayplanOptionPreselected())) ||
                  (code == "lpcheckout_paycard" && code === selectedPaymentOption && !this.isPayplanOptionPreselected())) {

                    this.selectPaymentMethod();
                }
            },

            /**
             * Show text title
             * @returns {Boolean}
             */
            showTitleText: function () {
                var setting = window.checkoutConfig.payment.lpcheckout.titleSetting[this.getCode()];
                return (setting !== 'image_only');
            },

            /**
             * Show card image on title
             * @returns {Boolean}
             */
            showCardImage: function () {
                var setting = window.checkoutConfig.payment.lpcheckout.titleSetting[this.getCode()];
                return (setting !== 'text_only');
            },

            /**
             * Get instructions set in admin for the payment method.
             * @returns {String}
             */
            getInstructions: function () {
                return window.checkoutConfig.payment.lpcheckout.instructions[this.getCode()];
            },

            /**
             * Get available payment option value set by admin
             * @returns {String}
             */
            getAvailablePaymentOption: function () {
                return window.checkoutConfig.payment.lpcheckout.availablePaymentOption;
            },

            /**
             * Check if customer toggled to BNPL from a widget
             * @returns {Boolean}
             */
            isPayplanOptionPreselected: function () {
                return ($.cookie('lpInstallmentToken')!== null && $.cookie('lpInstallmentToken'));
            },

            /**
             * Get Limepay iframe ID.
             * @returns {String}
             */
            getLimepayInstanceId: function() {
                var code = this.getCode();
                return code + '_instance';
            },

            /**
             * Get customer's email set in checkout or logged in user email.
             * @returns {String}
             */
            getEmail: function () {
                if(quote.guestEmail) return quote.guestEmail;
                else return window.checkoutConfig.customerData.email;
            },

            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        'limepay_payment_token': this.limepayPaymentToken,
                        'limepay_payment_action': this.limepayPaymentActionObject ? JSON.stringify(this.limepayPaymentActionObject) : null
                    }
                };
            },

            /**
             * Select Limepay payment method by default
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
                var code = this.getCode();
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            /**
             * Hide loader when iframe is fully loaded.
             */
            onPlaceHolderRendered: function () {
                var self = this;
                var params = {
                  instanceId: self.getLimepayInstanceId()
                };

                self.limepayPaymentToken = null;

                newLimepayCheckout(params, function(err) {
                    if (err) {
                        console.log(err);
                        return;
                    }
                    self.limepayCheckout = limepay.getLimepayCheckout(self.getLimepayInstanceId());
                    self.renderLimepayCheckout();
                });
            },

            initLimepayEventHandlers: function() {
                var self = this;
                self.limepayCheckout.errorHandler(self.limepyCheckoutErrorHandler.bind(self));
                self.limepayCheckout.eventHandler(self.limepayCheckoutEventHandler.bind(self));
            },

            limepyCheckoutErrorHandler: function() {
                this.scrollIntoViewLimepay();
                fullScreenLoader.stopLoader();
            },

            limepayCheckoutEventHandler: function(lpEvent) {
                if (lpEvent.eventName == 'limepay_card_3DS_pending') {
                  this.scrollIntoViewLimepay();
                  fullScreenLoader.stopLoader();
                }
            },

            getPlaceHolderId: function() {
                return this.getCode() + '_placeholder';
            },

            handlePaymentToken: function(paymentToken) {
                var self = this;
                self.limepayPaymentToken = paymentToken;
                self.resetLimepayPaymentActionObject();
                self.placeOrder();
            },

            /**
             * If the Limepay payment token is not already available,
             * initiate submit process inside iframe.
             * @returns {Boolean}
             */
            requestPaymentToken: function() {
                if (this.limepayPaymentToken) {
                    return true;
                }
                this.showFullScreenLoader();

                this.limepayCheckout.submit();

                return false;
            },

            /**
             * Shows full screen loader
             * fullScreenLoader.startLoader() will not be used with BSS OSC
             */
            showFullScreenLoader: function() {
                if (_.isUndefined(window.checkoutConfig.bssOsc)) {
                    fullScreenLoader.startLoader();
                }
            },

            hideFullScreenLoader: function() {
                fullScreenLoader.stopLoader();
            },

            /**
             * Place order.
             *
             * @returns {Boolean}.
             */
             placeOrder: function (data, event) {
                 var self = this;

                 if (event) {
                     event.preventDefault();
                 }

                 var customErrorHandler = this.handlePlaceOrderErrors.bind(this);

                 if (this.validate() &&
                     additionalValidators.validate() &&
                     self.isPlaceOrderActionAllowed() === true
                 ) {
                     if (!this.requestPaymentToken()) {
                         return true;
                     }
                     self.isPlaceOrderActionAllowed(false);
                     self.getPlaceOrderDeferredObject()
                         .fail(customErrorHandler)
                         .done(
                             function () {
                                 self.afterPlaceOrder();

                                 if (self.redirectAfterPlaceOrder) {
                                     redirectOnSuccessAction.execute();
                                 }
                             }
                         );

                     return true;
                 }
                 return false;
             },

             handlePlaceOrderErrors: function (result) {
                 var self = this;
                 var status = result.status + " " + result.statusText;
                 var respMsg = result.responseJSON.message;

                 fullScreenLoader.stopLoader();
                 self.isPlaceOrderActionAllowed(true);
                 if (limepay.isPaymentActionRequired(respMsg))
                 {
                     var paymentActionObject = self.getPaymentActionObject(respMsg);
                     self.openPayActionModal(paymentActionObject);
                 } else {
                    self.resetLimepayPaymentToken();
                 }
             },

             resetLimepayPaymentToken: function() {
                this.limepayPaymentToken = null;
             },

             resetLimepayPaymentActionObject: function() {
                this.limepayPaymentActionObject = null;
             },

             getPaymentActionObject: function( message ) {
                var payActParams = message.split('::');
                return JSON.parse(payActParams[1]);
             },

            openPayActionModal: function( paymentActionObject ) {
               var self = this;
               self.limepayCheckout.handlePaymentActionRequired( paymentActionObject,
                   function() {
                      self.limepayPaymentActionObject = paymentActionObject;
                      self.isPlaceOrderActionAllowed(true);
                      self.placeOrder();
                   },
                   function() {
                     self.resetLimepayPaymentToken();
                     self.resetLimepayPaymentActionObject();
                     self.isPlaceOrderActionAllowed(true);
                   }
               );
         		},

            /**
             * If the Limepay iframe if off screen,
             * scroll to Limepay iframe
             */
            scrollIntoViewLimepay: function () {
                var code = this.getCode();
                var ifrElem = document.getElementById(this.getPlaceHolderId());
                ifrElem.scrollIntoView();
            }
        });
    }
);
