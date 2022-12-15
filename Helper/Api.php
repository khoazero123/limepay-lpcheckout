<?php

namespace Limepay\Lpcheckout\Helper;

use Limepay\Lpcheckout\Helper\Logger;
use Limepay\Lpcheckout\Model\Config;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Helper\ImageFactory as ImageHelper;

class Api
{
    const LP_PROD_API           = 'https://api.limepay.com.au';
    const LP_SANDBOX_API        = 'https://api.sandbox.limepay.com.au';
    const LP_TST_API            = 'https://api.tst.limep.net';
    const LP_DEV_API            = 'https://api.dev.limep.net';

    const LP_PROD_API_LEGACY    = 'https://www.limepay.com.au/api/v1';
    const LP_SANDBOX_API_LEGACY = 'https://www.sandbox.limepay.com.au/api/v1';
    const LP_TST_API_LEGACY     = 'https://www.tst.limepay.com.au/api/v1';
    const LP_DEV_API_LEGACY     = 'https://www.dev.limepay.com.au/api/v1';

    public function __construct(
        \Limepay\Lpcheckout\Model\Config $config,
        ProductFactory $productFactory,
        ImageHelper $imageHelper
    ) {
        $this->config         = $config;
        $this->productFactory = $productFactory;
        $this->imageHelper    = $imageHelper;
    }

    public function createOrder( $orderParams )
    {
        $resp = $this->request( $orderParams, 'orders' );
        return $resp;
    }

    public function orderPay( $payParams )
    {
        $payData = [
            'paymentToken' => $payParams['paymentToken'],
            'request3DS' => $payParams['request3DS'],
            'paymentActionRequired' => $payParams['paymentAction']
        ];
        $resp = $this->request( $payData, 'orders/' . $payParams['merchantOrderId'] . '/pay' );
        return $resp;
    }

    public function refund( $refundParams )
    {
        $isLegacy = $this->isLegacyTransaction( $refundParams['transactionId'] );
        $refundData = [
            'transactionId' => $refundParams['transactionId'],
            'refundAmount' => [
              'amount' => $refundParams['amount'],
              'currency' => $refundParams['currency']
            ]
        ];
        $resp = $this->request( $refundData, 'refunds', 'POST', $isLegacy );
        return $resp;
    }

    public function upsertCustomer( $custParams )
    {
        $custData = [
            'internalCustomerId' => $custParams['internalCustomerId'],
            'emailAddress' => $custParams['emailAddress']
        ];
        $resp = $this->request( $custData, 'customers/plugin', 'POST' );
        return $resp;
    }

    public function signInCustomer( $custParams )
    {
        $custData = [
            'customerId' => $custParams['customerId']
        ];
        $resp = $this->request( $custData, 'authn/tenants/accounts:signinCustomerWithCustomerId', 'POST' );
        return $resp;
    }

    protected function isLegacyTransaction( $transactionId )
    {
        $txExp = explode( '_', $transactionId );
        return ( $txExp[0] !== 'tran' );
    }

    protected function request( $params, $resource, $method = 'POST', $legacy = false )
    {
        //call your capture api here, incase of error throw exception.
        $secretKey = $this->config->getConfigData( 'lpcheckout', 'secretkey' );
        $dataString = json_encode( $params );

        $url = $this->getApiUrl( $legacy );
        $url .= '/' . $resource;

        //open connection
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $dataString );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION ,1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 120 ); // Timeout on connect (2 minutes)
        $httpHeader = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen( $dataString )
        ];
        if ( $legacy ) {
            curl_setopt( $ch, CURLOPT_USERPWD, $secretKey . ':' );
        }else {
            array_push( $httpHeader, 'Limepay-SecretKey: ' . $secretKey );
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $httpHeader );

        $resp = array();
        $result = curl_exec( $ch );

        if ( $result === false ) {
            $errorMsg = curl_error( $ch );
            $errorMsg = is_null( $errorMsg ) ? 'Unknown error occured while calling Limepay API' : $errorMsg;
            $resp = [
              'data' => [ 'message' => $errorMsg ]
            ];
            \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay API error: ' . $errorMsg );
            \Limepay\Lpcheckout\Helper\Logger::log( [
               'resource' => $resource,
               'method' => $method,
               'requestBody' => $dataString
            ] );
        }else {
            $httpStatusCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            try {
                $data = json_decode( $result, true );
                $data = is_null( $data ) ? array() : $data;
                $resp = [
                  'httpStatusCode' => $httpStatusCode,
                  'data' => $data
                ];
            } catch(Exception $e) {
                $resp = [
                  'httpStatusCode' => $httpStatusCode,
                  'data' => []
                ];
            }
        }
        curl_close($ch);

        return $resp;
    }

    private function getApiUrl( $legacy ) {
        $env = $this->getEnv();
      	$url = $legacy ? self::LP_DEV_API_LEGACY : self::LP_DEV_API;

      	if ($env === 'live') {
      		  $url = $legacy ? self::LP_PROD_API_LEGACY : self::LP_PROD_API;
      	} else if ($env == 'test' || $env == 'sandbox') {
      		  $url = $legacy ? self::LP_SANDBOX_API_LEGACY : self::LP_SANDBOX_API;
      	} else if ($env == 'tst') {
      		  $url = $legacy ? self::LP_TST_API_LEGACY : self::LP_TST_API;
      	}
      	return $url;
    }

    private function getEnv() {
        $publishableKey = $this->config->getConfigData( 'lpcheckout', 'publishablekey' );
        $pkExp = explode( '_', $publishableKey );
        $env = $pkExp[0];
        return $env;
    }
}
