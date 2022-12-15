<?php

namespace Limepay\Lpcheckout\Model;

use Limepay\Lpcheckout\Helper\Logger;

class LimepayCustomer extends \Magento\Framework\Model\AbstractModel
{
    var $_limepayCustomer = null;
    var $_defaultPaymentMethod = null;

    public $customerCard = null;
    public $paymentMethodsCache = [];

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Limepay\Lpcheckout\Model\Customer $customer,
        \Limepay\Lpcheckout\Helper\Generic $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->customer = $customer;
        $this->_customerSession = $customerSession;
        $this->_sessionManager = $sessionManager;
        $this->_registry = $registry;
        $this->_appState = $context->getAppState();
        $this->_eventManager = $context->getEventDispatcher();
        $this->_cacheManager = $context->getCacheManager();
        $this->_resource = $resource;
        $this->_resourceCollection = $resourceCollection;
        $this->_logger = $context->getLogger();
        $this->_actionValidator = $context->getActionValidator();

        if (method_exists($this->_resource, 'getIdFieldName')
            || $this->_resource instanceof \Magento\Framework\DataObject
        ) {
            $this->_idFieldName = $this->_getResource()->getIdFieldName();
        }

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Limepay\Lpcheckout\Model\ResourceModel\LimepayCustomer');
    }

    public function updateSessionId()
    {
        if (!$this->getLimepayId()) return;
        if ($this->helper->isAdmin()) return;

        $sessionId = $this->_customerSession->getSessionId();
        if ($sessionId != $this->getSessionId())
        {
            $this->setSessionId($sessionId);
            $this->save();
        }
    }

    public function signInCustomer()
    {
        try {
            if (!$this->getLimepayId())
            {
                $this->createLimepayCustomer();
            }

            $resp = $this->customer->signin( $this->getLimepayId() );
            $customToken = $resp['customToken'];

            return $customToken;

        }catch (Exception $e) {
            return null;
        }
    }

    public function createLimepayCustomer()
    {
        $params = $this->getLimepayCustomerParams();

        return $this->createNewLimepayCustomer( $params );
    }

    public function getLimepayCustomerParams()
    {
        $params = [];

        $customer = $this->helper->getMagentoCustomer();

        if ($customer)
        {
            $params = [
                'firstName' => $customer->getFirstname(),
                'lastName' => $customer->getLastname(),
                'emailAddress' => $customer->getEmail(),
                'customerId' => $customer->getEntityId()
            ];

            return $params;

        }else {
            throw new \Magento\Framework\Exception\Exception( 'Cannot create Limepay accounts for unregistered users' );
        }
    }

    public function createNewLimepayCustomer( $params )
    {
        try
        {
            $limepayCustomerId = $this->customer->create( $params );

            $this->setLimepayId( $limepayCustomerId );
            $this->setPublicKey( $this->helper->getPublishableKey() );
            $this->setCustomerId( $params['customerId'] );
            $this->setCustomerEmail( $params['emailAddress'] );

            $this->save();

            return null;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

}
