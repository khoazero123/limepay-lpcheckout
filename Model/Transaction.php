<?php
namespace Limepay\Lpcheckout\Model;

use Limepay\Lpcheckout\Helper\Logger;
use Limepay\Lpcheckout\Model\Config;

class Transaction
{
    protected $_transactionId;
    protected $_merchantOrder;
    protected $_paymentAction;
    protected $_paymentMethodCode;

    public function __construct(
        \Limepay\Lpcheckout\Helper\Api $api,
        \Limepay\Lpcheckout\Helper\Generic $helper,
        \Limepay\Lpcheckout\Model\Config $config,
        \Limepay\Lpcheckout\Model\PaymentAction $paymentAction,
        \Limepay\Lpcheckout\Model\MerchantOrder $merchantOrder
    ) {
        $this->api            = $api;
        $this->helper         = $helper;
        $this->config         = $config;
        $this->paymentAction  = $paymentAction;
        $this->merchantOrder  = $merchantOrder;
    }

    public function initOrder( $paymentMethodCode, $order, $paymentAction = null )
    {
        $this->_paymentMethodCode = $paymentMethodCode;
        $this->paymentAction->init( $paymentMethodCode );
        $this->merchantOrder->create( $order );

        if ( isset( $paymentAction ) && !empty( $paymentAction ) ) {
            $this->paymentAction->process( $paymentAction );
        }
    }

    public function create( $paymentToken, $request3DS )
    {
        $paymentAction = null;
        if ( $this->paymentAction->isPaymentActionRequired() )
            $paymentAction = $this->paymentAction->_paymentAction;

        $payParams = [
            'paymentToken' => $paymentToken,
            'request3DS' => $request3DS,
            'paymentAction' => $paymentAction,
            'merchantOrderId' => $this->merchantOrder->_merchantOrderId
        ];

        $resp = $this->api->orderPay( $payParams );
        $transactionData = $this->processOrderPayAPIResponse( $resp );
        $this->_transactionId = $transactionData['transactionId'];
        $this->paymentAction->clearPaymentAction();
        $this->merchantOrder->clearOrder();
        return $transactionData;
    }

    public function refund( $transactionId, $amount, $currency )
    {
        $refundParams = [
            'transactionId' => $transactionId,
            'amount' => $this->helper->convertToCents( $amount ),
            'currency' => $currency
        ];

        $refundResp = $this->api->refund( $refundParams );
        $this->processRefundAPIResponse( $refundResp );
    }

    private function processOrderPayAPIResponse( $payResp )
    {
        if ( array_key_exists( 'httpStatusCode', $payResp ) ) {
            $payStatusCode = $payResp['httpStatusCode'];

            if ($payStatusCode == 200) {
                return $payResp["data"];
            }
            else if ( $payStatusCode == 403 ) {
                if ( !array_key_exists( 'paymentActionRequired', $payResp['data'] ) ) {
                    $errorMsg = 'Payment failed.';
                    \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay order/pay error: Access denied' );
                    \Limepay\Lpcheckout\Helper\Logger::log( ['httpStatusCode' => $payStatusCode, 'responseData' => $payResp] );
                    throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
                }
                if ( $this->helper->isAdmin() ) {
                    $errorMsg = 'Card requires 3DS authentication by customer.';
                    throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
                }
                $this->handlePaymentActionRequired( $payResp['data']['paymentActionRequired'] );
            }
            else {
                $errorMsg = $this->helper->getErrorMessage( $payResp, 'An unknown error occurred while processing the payment with Limepay' );
                \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay order/pay error: Unexpected httpStatusCode' );
                \Limepay\Lpcheckout\Helper\Logger::log( ['httpStatusCode' => $payStatusCode, 'responseData' => $payResp] );
                throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
            }
        }else {
            $errorMsg = $this->helper->getErrorMessage($payResp, 'An unknown error occurred while processing the payment with Limepay');
            \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay order/pay error: API call failed' );
            \Limepay\Lpcheckout\Helper\Logger::log( ['responseData' => $payResp] );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }
    }

    private function processRefundAPIResponse( $refundResp )
    {
        if ( array_key_exists( 'httpStatusCode', $refundResp ) ) {
            $refundStatusCode = $refundResp['httpStatusCode'];
            if ( $refundStatusCode == 200 ) {
                return;
            }else {
                $errorMsg = $this->helper->getErrorMessage( $refundResp, 'Failed to issue a refund via Limepay' );
                \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay refund error: Unexpected httpStatusCode' );
                \Limepay\Lpcheckout\Helper\Logger::log( ['httpStatusCode' => $refundStatusCode, 'responseData' => $refundResp] );
                throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
            }
        }else {
            $errorMsg = $this->helper->getErrorMessage( $refundResp, 'Failed to issue a refund via Limepay' );
            \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay refund error: API call failed' );
            \Limepay\Lpcheckout\Helper\Logger::log( ['responseData' => $refundResp] );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }
    }

    private function handlePaymentActionRequired( $paymentAction )
    {
        $this->paymentAction->setPaymentAction( $paymentAction );
        $errorMsg = 'Payment Action required::' . json_encode( $paymentAction );
        throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
    }
}
