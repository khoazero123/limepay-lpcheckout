<?php
namespace Limepay\Lpcheckout\Helper;

use Magento\Backend\Model\Session;
use Magento\Framework\Validator\Exception;
use Magento\Store\Model\ScopeInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\LocalizedException;

class Generic
{
    public $currentCustomer = null;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Session\Quote $backendSessionQuote,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Limepay\Lpcheckout\Model\ResourceModel\LimepayCustomer\Collection $customerCollection,
        \Limepay\Lpcheckout\Model\Config $config
    ) {
        $this->customerSession = $customerSession;
        $this->backendSessionQuote = $backendSessionQuote;
        $this->request = $request;
        $this->appState = $appState;
        $this->storeManager = $storeManager;
        $this->order = $order;
        $this->invoice = $invoice;
        $this->creditmemo = $creditmemo;
        $this->customerRegistry = $customerRegistry;
        $this->sessionManager = $sessionManager;
        $this->customerCollection = $customerCollection;
        $this->config = $config;
    }

    public function getBackendSessionQuote()
    {
        return $this->backendSessionQuote->getQuote();
    }

    public function getStoreId()
    {
        if ($this->isAdmin())
        {
            if ($this->request->getParam('order_id', null))
            {
                $order = $this->order->load($this->request->getParam('order_id', null));
                return $order->getStoreId();
            }
            if ($this->request->getParam('invoice_id', null))
            {
                $invoice = $this->invoice->load($this->request->getParam('invoice_id', null));
                return $invoice->getStoreId();
            }
            else if ($this->request->getParam('creditmemo_id', null))
            {
                $creditmemo = $this->creditmemo->load($this->request->getParam('creditmemo_id', null));
                return $creditmemo->getStoreId();
            }
            else
            {
                $quote = $this->getBackendSessionQuote();
                return $quote->getStoreId();
            }
        }
        else
        {
            return $this->storeManager->getStore()->getId();
        }
    }

    public function convertToCents( $amount )
    {
        return round( $amount * 100 );
    }

    public function getOrderGrandTotal( $order )
    {
        return $this->convertToCents( $order->getGrandTotal() );
    }

    public function isAdmin()
    {
        $areaCode = $this->appState->getAreaCode();

        return $areaCode == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
    }

    public function getErrorMessage($resp, $altMessage)
    {
        $errorMsg = $altMessage;
        if ( array_key_exists( 'errorCode', $resp['data'] ) ) {
            $errorMsg = $this->getApiErrorMessage( $resp['data']['errorCode'], $resp['data']['message'] );
        }
        return !is_null( $errorMsg ) ? $errorMsg : 'Unknown error occured';
    }

    public function getApiErrorMessage($apiErrorCode, $apiMessage)
    {
        $errorList = array(
            'unknown_charge_error'  => 'Unknown error occurred while processing the card',
            'do_not_honor'          => 'Please contact your card issuer',
            'expired_card'          => 'Card expired',
            'fraudulent'            => 'Suspected fraudulent transaction',
            'incorrect_cvc'         => 'Incorrect CVC',
            'insufficient_funds'    => 'Insufficient funds',
            'invalid_cvc'           => 'Invalid CVC',
            'invalid_expiry_month'  => 'Invalid expiry month',
            'invalid_expiry_year'   => 'Invalid expiry year',
            'pickup_card'           => 'Card not allowed',
            'processing_error'      => 'An error occurred while processing the card',
            'stolen_card'           => 'Card is reported stolen',
            'bad_request'           => 'Payment failed'
        );
        if (array_key_exists($apiErrorCode, $errorList)) {
            return $errorList[$apiErrorCode];
        }
        return $apiMessage;
    }

    public function getPublishableKey()
    {
        return $this->config->getConfigData('lpcheckout', 'publishablekey');
    }

    public function getCustomerModel()
    {
        $pk = $this->getPublishableKey();

        $customerId = $this->getCustomerId();
        $model = null;

        if (is_numeric($customerId) && $customerId > 0)
        {
            $model = $this->customerCollection->getByCustomerId($customerId, $pk);
            if ($model && $model->getId())
            {
                $model->updateSessionId();
                $this->currentCustomer = $model;
            }
        }

        if (!$this->currentCustomer)
            $this->currentCustomer = \Magento\Framework\App\ObjectManager::getInstance()->create('Limepay\Lpcheckout\Model\LimepayCustomer');

        return $this->currentCustomer;
    }

    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getCustomerId()
    {
        if ($this->customerSession->isLoggedIn())
        {
            return $this->customerSession->getCustomerId();
        }

        return null;
    }

    public function getMagentoCustomer()
    {
        if ($this->customerSession->getCustomer()->getEntityId())
            return $this->customerSession->getCustomer();

        $customerId = $this->getCustomerId();
        if (!$customerId) return;

        $customer = $this->customerRegistry->retrieve($customerId);

        if ($customer->getEntityId())
            return $customer;

        return null;
    }

    public function isGuest()
    {
        return !$this->customerSession->isLoggedIn();
    }
}
