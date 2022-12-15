<?php
namespace Limepay\Lpcheckout\Observer\Checkout;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

use \Magento\Checkout\Model\Session as CheckoutSession;

class DeleteSession implements ObserverInterface {

    /** @var CheckoutSession */
    protected $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession) {
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(Observer $observer) {
        $this->checkoutSession->unsSessionReservedOrderId();
    }
}