<?php
namespace Limepay\Lpcheckout\Block;

/**
 * Base payment information block
 *
 * @api
 * @since 100.0.2
 */
class Form extends \Magento\Payment\Block\Form
{
    protected $config;
    protected $sessionQuote;
    protected $countryFactory;

    public function __construct(
        \Limepay\Lpcheckout\Model\Config $config,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\View\Element\Template\Context $context,
		    \Magento\Directory\Model\CountryFactory $countryFactory
    )
    {
        $this->config = $config;
        $this->sessionQuote = $sessionQuote;
        $this->countryFactory = $countryFactory;
        return parent::__construct($context);
    }

    public function getCountryName($countryCode) {
        try {
            $country = $this->countryFactory->create()->loadByCode($countryCode);
            return $country->getName();
        } catch (\Exception $ex) {
            return 'Australia';
        }
    }

    public function getLimepayCheckoutParams() {
        $method = $this->getMethod();
        $publishableKey = $method->getConfigData( 'publishablekey' );
        $availablePaymentOption = $method->getConfigData( 'available_payment_options' );
        $orderQuote = $this->sessionQuote->getQuote();

        $firstName = $orderQuote->getBillingAddress()->getData( 'firstname' );
        $lastName = $orderQuote->getBillingAddress()->getData( 'lastname');
        $middleName = $orderQuote->getBillingAddress()->getData( 'middlename' );
        $address = rawurlencode($orderQuote->getBillingAddress()->getData( 'street' ) . ' ' .
            $orderQuote->getBillingAddress()->getData( 'city' ) . ' ' .
            $orderQuote->getBillingAddress()->getData( 'region' ) . ' ' .
            $orderQuote->getBillingAddress()->getData( 'postcode' ) . ' ' .
            $this->getCountryName($orderQuote->getBillingAddress()->getData( 'country_id') )
        );
        $phoneNumber = $orderQuote->getBillingAddress()->getData( 'telephone' );
        $email = $orderQuote->getBillingAddress()->getData( 'email' );
        $amount = round( $orderQuote->getGrandTotal() * 100);
        $currency = $orderQuote->getQuoteCurrencyCode();

        return \Zend_Json::encode([
            'publishableKey' => $publishableKey,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'middleName' => $middleName,
            'address' => $address,
            'phoneNumber' => $phoneNumber,
            'email' => $email,
            'amount' => $amount,
            'currency' => $currency,
            'availablePaymentOption' => $availablePaymentOption
        ]);
    }

}
