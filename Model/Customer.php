<?php
namespace Limepay\Lpcheckout\Model;

use Limepay\Lpcheckout\Helper\Logger;
use Limepay\Lpcheckout\Helper\Generic;
use Limepay\Lpcheckout\Model\Config;

class Customer
{

    public function __construct(
        \Limepay\Lpcheckout\Helper\Api $api,
        \Limepay\Lpcheckout\Helper\Generic $helper,
        \Limepay\Lpcheckout\Model\Config $config,
        \Limepay\Lpcheckout\Model\ResourceModel\LimepayCustomer\Collection $customerCollection
    ) {
        $this->api            = $api;
        $this->helper         = $helper;
        $this->config         = $config;
        $this->customerCollection = $customerCollection;
    }

    public function create( $customerData )
    {
        $customerParams = [
            'internalCustomerId' => $customerData['customerId'],
            'emailAddress' => $customerData['emailAddress']
        ];
        $customerResp = $this->api->upsertCustomer( $customerParams );

        if ( array_key_exists( 'httpStatusCode', $customerResp ) ) {
            $customerStatusCode = $customerResp['httpStatusCode'];

            if ($customerStatusCode == 200) {
                return $customerResp['data']['customerId'];

            }else {
                $errorMsg = 'An unknown error occurred while upserting the customer Limepay';
                \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay customer upsert error: Unexpected httpStatusCode' );
                \Limepay\Lpcheckout\Helper\Logger::log( ['httpStatusCode' => $customerStatusCode, 'responseData' => $customerResp] );
                throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
            }
        }
        else {
            $errorMsg = 'An unknown error occurred while upserting the customer with Limepay';
            \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay customer upsert error: API call failed' );
            \Limepay\Lpcheckout\Helper\Logger::log( ['responseData' => $customerResp] );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }
    }

    public function signin( $customerId )
    {
        $customerParams = [
            'customerId' => $customerId
        ];
        $customerResp = $this->api->signInCustomer( $customerParams );

        if ( array_key_exists( 'httpStatusCode', $customerResp ) ) {
            $customerStatusCode = $customerResp['httpStatusCode'];

            if ($customerStatusCode == 200) {
                return $customerResp['data'];

            }else {
                $errorMsg = 'An unknown error occurred while signin the customer Limepay';
                \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay customer signin error: Unexpected httpStatusCode' );
                \Limepay\Lpcheckout\Helper\Logger::log( ['httpStatusCode' => $customerStatusCode, 'responseData' => $customerResp] );
                throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
            }
        }
        else {
            $errorMsg = 'An unknown error occurred while signin the customer with Limepay';
            \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay customer signin error: API call failed' );
            \Limepay\Lpcheckout\Helper\Logger::log( ['responseData' => $customerResp] );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }
    }

}
