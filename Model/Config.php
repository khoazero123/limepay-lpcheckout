<?php
namespace Limepay\Lpcheckout\Model;

use Limepay\Lpcheckout\Helper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Limepay\Lpcheckout\Helper\Config $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    public function getConfigData($method_code, $field, $storeId = null)
    {
        $code = $method_code;
        if (empty($storeId))
            $storeId = $this->helper->getStoreId();

        $defaultLimepayConfig = [
            'active',
            'publishablekey',
            'secretkey',
            'payment_action',
            'order_status',
            'request_3ds',
            'available_payment_options',
            'individual_payment_options',
            'minimum_amount_3ds'
        ];
        if (in_array($field, $defaultLimepayConfig))
            $code = 'lpcheckout';

        $path = 'payment/' . $code . '/' . $field;
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);

        return $value;
    }

    public function getLimepayEnvToUse()
    {
        $publishableKey = $this->getConfigData( 'lpcheckout', 'publishablekey' );
        $pkExp = explode( '_', $publishableKey );
        $env = $pkExp[0];
        return $env;
    }

    public function isLimepayProdEnv()
    {
        $env = $this->getLimepayEnvToUse();
        return $env === 'live';
    }

    public function getWebUrl()
    {
        $env = $this->getLimepayEnvToUse();
        $url = '';
        switch ($env) {
            case 'dev':
                $url = 'https://checkout.dev.limepay.com.au';
                break;
            case 'tst':
                $url = 'https://checkout.tst.limepay.com.au';
                break;
            case 'sandbox':
                $url = 'https://checkout.sandbox.limepay.com.au';
                break;
            default:
                $url = 'https://checkout.limepay.com.au';
        }
        return $url;
    }
}
