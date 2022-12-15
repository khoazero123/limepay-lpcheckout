<?php

namespace Limepay\Lpcheckout\Model\Config;

use Magento\Checkout\Model\ConfigProviderInterface;
use Limepay\Lpcheckout\Model\Config;
use Limepay\Lpcheckout\Helper\Logger as LpLogger;

/**
 * Class CheckoutConfigProvider
 */
class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @param CurrentCustomer $currentCustomer
     * @param AgreementFactory $agreementFactory
     */
    public function __construct(
        \Limepay\Lpcheckout\Model\Config $config,
        \Limepay\Lpcheckout\Helper\Generic $helper
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->customer = $helper->getCustomerModel();

    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $lpWebUrl = $this->config->getWebUrl();
        $lpcheckoutConfig = array();
        $lpcheckoutConfig['instructions'] = array();
        $lpcheckoutConfig['instructions']['lpcheckout'] = $this->getStoreConfigValue( 'instructions' );
        $lpcheckoutConfig['instructions']['lpcheckout_payplan'] = $this->getStoreConfigValue( 'instructions', 'lpcheckout_payplan' );
        $lpcheckoutConfig['instructions']['lpcheckout_paycard'] = $this->getStoreConfigValue( 'instructions', 'lpcheckout_paycard' );

        $lpcheckoutConfig['titleSetting'] = array();
        $lpcheckoutConfig['titleSetting']['lpcheckout'] = $this->getStoreConfigValue( 'title_setting' );
        $lpcheckoutConfig['titleSetting']['lpcheckout_payplan'] = $this->getStoreConfigValue( 'title_setting', 'lpcheckout_payplan' );
        $lpcheckoutConfig['titleSetting']['lpcheckout_paycard'] = $this->getStoreConfigValue( 'title_setting', 'lpcheckout_paycard' );

        $lpcheckoutConfig['publishablekey'] = $this->getStoreConfigValue( 'publishablekey' );
        $lpcheckoutConfig['defaultPaymentMethod'] = $this->getStoreConfigValue( 'default_payment_method' );
        $lpcheckoutConfig['availablePaymentOption'] = $this->getStoreConfigValue( 'available_payment_options' );
        $lpcheckoutConfig['individualPaymentOption'] = $this->getStoreConfigValue( 'individual_payment_options' );
        $lpcheckoutConfig['selectedPaymentOption'] = $this->getSelectedPaymentOption();
        $lpcheckoutConfig['primaryColor'] = $this->getStoreConfigValue( 'primary_color' );
        $lpcheckoutConfig['isLoggedIn'] = $this->helper->isCustomerLoggedIn();
        $lpcheckoutConfig['customToken'] = $this->getLimepayCustomToken();

        $config = [
            'payment' => [
                'lpcheckout' => $lpcheckoutConfig
            ]
        ];

        return $config;
    }

    public function getStoreConfigValue( $fieldId, $code = 'lpcheckout' )
    {
        return $this->config->getConfigData( $code, $fieldId );
    }

    private function getLimepayCustomToken()
    {
        try {
            if ($this->helper->isCustomerLoggedIn())
            {
                return $this->customer->signInCustomer();
            }
            return null;
        }catch (Exception $e) {
            return null;
        }
    }

    private function getSelectedPaymentOption() {
        $selectedPaymentOption = 'lpcheckout';

        $indivPayOptions = $this->getStoreConfigValue( 'individual_payment_options' );
        if ( $indivPayOptions == 1 ) {
            $availPayOptions = $this->getStoreConfigValue( 'available_payment_options' );
            if ( $availPayOptions == 'payplan' ) {
                $selectedPaymentOption = 'lpcheckout_payplan';
            } else if ( $availPayOptions == 'paycard' ) {
                $selectedPaymentOption = 'lpcheckout_paycard';
            } else {
                $sortOrderPayplan = $this->getStoreConfigValue( 'sort_order', 'lpcheckout_payplan' );
                $sortOrderPaycard = $this->getStoreConfigValue( 'sort_order', 'lpcheckout_paycard' );

                if ($sortOrderPaycard < $sortOrderPayplan) {
                    $selectedPaymentOption = 'lpcheckout_paycard';
                } else {
                    $selectedPaymentOption = 'lpcheckout_payplan';
                }
            }
        }

        return $selectedPaymentOption;
    }
}
