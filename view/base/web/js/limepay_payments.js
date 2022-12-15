// Copyright © Limepay
//
// @package    Limepay_Lpcheckout
// @version    3.1.0

var newLimepayCheckout = function(params, callback)
{
    if (typeof callback == "undefined")
        callback = null;

    require(['lpcheckoutjs'], function(lpcheckoutjs)
    {
        limepay.initLpCheckoutJs(params, callback);
    });
};

var limepay =
{
  version: "3.1.0",
  checkout: {},
  adminCheckout: null,

  initLpCheckoutJs: function(params, callback)
  {
      var instanceId = params.instanceId;
      var isAdmin = params.isAdmin;
      var message = null;

      try
      {
          if (typeof instanceId == "undefined" || instanceId == null) {
            throw {message: 'Missing instanceId'};
          }
          if (params.isAdmin && !limepay.adminCheckout) {
            limepay.adminCheckout = LimepayCheckout.createCheckout();
          } else if (!(instanceId in limepay.checkout)) {
            limepay.checkout[instanceId] = LimepayCheckout.createCheckout();
          }
      }
      catch (e)
      {
          if (typeof e != "undefined" && typeof e.message != "undefined")
              message = 'Could not create LimepayCheckout: ' + e.message;
          else
            message = 'Could not create LimepayCheckout';
      }

      if (callback)
          callback(message);
      else
          console.log(message);
  },

  getLimepayCheckout: function(instanceId) {
    return limepay.checkout[instanceId];
  },

  isPaymentActionRequired: function( message ) {
     var payActParams = message.split('::');
     return ( payActParams.length > 0 && payActParams[0] == 'Payment Action required' );
  },

  initAdminCheckout: function()
  {
      if (typeof order != "undefined" && typeof order._submit != "undefined")
      {
          return;
      }
      else if (typeof order != "undefined" && typeof order._submit == "undefined")
      {
          order._submit = order.submit;
          order.submit = limepay.adminPlaceOrder;
      }
      limepay.adminCheckout = LimepayCheckout.createCheckout();
  },

  renderAdminCheckout: function (elementId, params)
  {
      if (!limepay.adminCheckout) {
          alert('Limepay admin checkout not initialised.');
          return;
      }

      var hidePayLaterOption = false;
      var hideFullPayOption = false;

      if (params.availablePaymentOption === "paycard") {
          hidePayLaterOption = true;
      } else if (params.availablePaymentOption === "payplan") {
          hideFullPayOption = true;
      }

      var initParams = {
          publicKey: params.publishableKey,
          email: params.email,
          customerFirstName: params.firstName,
          customerMiddleName: params.middleName,
          customerLastName: params.lastName,
          customerResidentialAddress: params.address,
          phone: params.phoneNumber,
          hidePayLaterOption: hidePayLaterOption,
          hideFullPayOption: hideFullPayOption,
          paymentToken: limepay.handleAdminCheckoutPaymentToken.bind(limepay),
          platform: 'magento',
          platformVersion: '2',
          platformPluginVersion: limepay.version
      };

      var renderParams = {
         elementId: elementId,
         currency: params.currency,
         amount: params.amount,
         paymentType: 'paycard',
         showPayNow: false,
         showPayPlanSubmit: false,
      };

      limepay.adminCheckout.init(initParams);
      limepay.adminCheckout.render(renderParams);
  },

  handleAdminCheckoutPaymentToken: function (paymentToken) {
      document.getElementById('limepay-payment-token').value = paymentToken;
      order._submit();
  },

  adminPlaceOrder: function() {
      if (order.paymentMethod === 'lpcheckout') {
          limepay.adminCheckout.submit();
      }else {
          order._submit();
      }
  }

}
