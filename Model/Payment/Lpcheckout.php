<?php

namespace Limepay\Lpcheckout\Model\Payment;

use Limepay\Lpcheckout\Helper\Logger;
use Magento\Payment\Model\Method\Logger as MageLogger;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Limepay\Lpcheckout\Model\Config;
use Limepay\Lpcheckout\Helper\Generic;

class Lpcheckout extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "lpcheckout";

    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = false;
    protected $_isGateway                   = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;

    const ACTION_AUTHORIZE = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    protected $_formBlockType = \Limepay\Lpcheckout\Block\Form::class;
    protected $_infoBlockType = \Limepay\Lpcheckout\Block\Info::class;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param MageLogger $mageLogger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Limepay\Lpcheckout\Helper\Generic $helper,
        \Limepay\Lpcheckout\Model\Config $config,
        \Limepay\Lpcheckout\Model\Transaction $transaction,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        MageLogger $mageLogger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helper       = $helper;
        $this->transaction  = $transaction;
        $this->config       = $config;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $mageLogger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->initCanUseCheckout();
    }

    /**
     * Initialse this->_canUseCheckout value. Only "lpcheckout" method will be allowed in admin.
     *
     * @deprecated 100.2.0
     */
    private function initCanUseCheckout()
    {
        // If the publishable key is not set through admin, then make the Limepay unavailable
        if ( empty($this->config->getConfigData( 'lpcheckout', 'publishablekey' ) ) ) {
            $this->_canUseCheckout = false;
            return;
        }

        $indPayOptions = $this->config->getConfigData('lpcheckout', 'individual_payment_options');

        if ( $this->_code === 'lpcheckout' ) {
            if ($indPayOptions == 0) {
                $this->_canUseCheckout = true;
            }
        } else {
            if ($indPayOptions == 1) {
                $availPayOptions = $this->config->getConfigData('lpcheckout', 'available_payment_options');
                if (($availPayOptions === "0") || ($this->_code === "lpcheckout_payplan" && $availPayOptions === "payplan") || ($this->_code === "lpcheckout_paycard" && $availPayOptions === "paycard")) {
                    $this->_canUseCheckout = true;
                }
            }
        }
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|\Magento\Store\Model\Store $storeId
     *
     * @return mixed
     * @deprecated 100.2.0
     */
    public function getConfigData($field, $storeId = null)
    {
        $code = $this->_code;

        if ('order_place_redirect_url' === $field)
            return $this->getOrderPlaceRedirectUrl();

        return $this->config->getConfigData( $code, $field, $storeId );

    }

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function authorize( \Magento\Payment\Model\InfoInterface $payment, $amount )
    {
        parent::authorize( $payment, $amount );
        $limePaymentToken = $payment->getLimepayPaymentToken() ? $payment->getLimepayPaymentToken() : $payment->getAdditionalInformation('limepay_payment_token');
        $errorMsg = null;
        if ( ! $limePaymentToken ) {
            $errorMsg = 'Incorrect Limepay payment token';
        }

        if( $errorMsg ){
            throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
        }

        return $this;
    }

    /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $paymentToken = $payment->getLimepayPaymentToken() ? $payment->getLimepayPaymentToken() : $payment->getAdditionalInformation('limepay_payment_token');
        $paymentAction = $payment->getAdditionalInformation('limepay_payment_action');
        if ( isset( $paymentAction ) && !empty( $paymentAction ) ) {
            $paymentAction = json_decode( $paymentAction );
        }
        // If it's a payment via admin area, then 3DS should not be requested
        $request3DS = $this->getRequest3DS( $amount );

        if (!$paymentToken) {
            $errorMsg = 'Limepay payment token missing';
            \Limepay\Lpcheckout\Helper\Logger::log( $errorMsg );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }

        // If secret key is not set in admin cifigurations, thro an error
        if ( empty( $this->config->getConfigData( 'lpcheckout', 'secretkey' ) ) ) {
            $errorMsg = 'Limepay configurations error: Secret API key is not set';
            \Limepay\Lpcheckout\Helper\Logger::log( $errorMsg );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }

        // Init PaymentAction and MerchantOrder
        $this->transaction->initOrder( $this->_code, $order, $paymentAction );

        // Create new transaction
        $transactionData = $this->transaction->create( $paymentToken, $request3DS );

        // Complete order
        $this->completeOrder( $payment, $transactionData );

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

        $transactionId = $payment->getParentTransactionId();
        $currency =  $payment->getOrder()->getBaseCurrencyCode();

        $this->transaction->refund( $transactionId, $amount, $currency );

        return $this;
    }

    /**
     * Get config payment action url.
     *
     * Used to universalize payment actions when processing payment place.
     *
     * @return string
     * @api
     * @deprecated 100.2.0
     */
    public function getConfigPaymentAction()
    {
        return self::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     * @deprecated 100.2.0
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    )
    {
        return parent::isAvailable($quote);
    }

    private function completeOrder( $payment, $transactionData )
    {
        try {
            $payment->setTransactionId( $transactionData['transactionId'] );
            $payment->setIsTransactionClosed(1);
            // $additionalRawDetails = ['type' => $transactionData['type']];
            $payment->setAdditionalInformation( 'limepay_payment_type', $transactionData['type'] );
            $payment->setAdditionalInformation( 'limepay_transaction_id', $transactionData['transactionId'] );
            // $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $additionalRawDetails);
        } catch (\Exception $e) {
            $errorMsg = 'Limepay order completion error:';
            \Limepay\Lpcheckout\Helper\Logger::log( $errorMsg );
            \Limepay\Lpcheckout\Helper\Logger::log( ['exception' => $e] );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }
    }

    private function getRequest3DS( $amount )
    {
        $request3DSSetting = $this->helper->isAdmin() ? false : boolval( $this->config->getConfigData( $this->_code, 'request_3ds' ) );
        $minAmt3DSSetting = $this->helper->isAdmin() ? 0 : floatval( $this->config->getConfigData( $this->_code, 'minimum_amount_3ds' ) );
        return ( $request3DSSetting && $amount >= $minAmt3DSSetting );
    }
}
