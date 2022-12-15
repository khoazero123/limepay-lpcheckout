<?php
namespace Limepay\Lpcheckout\Plugin\Model\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Framework\App\ObjectManager;

class HandleReserveOrderId
{
    protected $_quoteResourceModel;
    protected $_checkoutSession;
    private $orderIncrementIdChecker;

    public function __construct(
        QuoteResourceModel $quoteResourceModel,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderIncrementIdChecker $orderIncrementIdChecker = null
    )
    {
        $this->_quoteResourceModel = $quoteResourceModel;
        $this->_checkoutSession = $checkoutSession;
        $this->orderIncrementIdChecker = $orderIncrementIdChecker ?: ObjectManager::getInstance()
            ->get(\Magento\Sales\Model\OrderIncrementIdChecker::class);
    }

	public function aroundReserveOrderId(Quote $quote, callable $proceed): Quote
    {
        if ($quote->getPayment()->getMethod() != 'lpcheckout') {
            return $proceed(); 
        }
        
        $sessionReservedOrderId = $this->_checkoutSession->getSessionReservedOrderId();
        if (!empty($sessionReservedOrderId) && $sessionReservedOrderId) {
            $quote->setReservedOrderId($this->_checkoutSession->getSessionReservedOrderId());
        } else {
            if (!$quote->getReservedOrderId()) {
                $quote->setReservedOrderId($this->_quoteResourceModel->getReservedOrderId($quote));
            } else {
                //checking if reserved order id was already used for some order
                //if yes reserving new one if not using old one
                if ($this->orderIncrementIdChecker->isIncrementIdUsed($quote->getReservedOrderId())) {
                    $quote->setReservedOrderId($this->_quoteResourceModel->getReservedOrderId($quote));
                }
            }
            $this->_checkoutSession->setSessionReservedOrderId($quote->getReservedOrderId());
        }
        
        return $quote;
    }
}