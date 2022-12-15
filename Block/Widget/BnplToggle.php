<?php

namespace Limepay\Lpcheckout\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class BnplToggle extends Template implements BlockInterface
{
    protected $_template = "widget/bnpl_toggle.phtml";
    protected $_checkoutSession;
    protected $_quoteRepository;
    protected $_lpHelper;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Limepay\Lpcheckout\Helper\Data $lpHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteRepository = $quoteRepository;
        $this->_lpHelper = $lpHelper;
        parent::__construct($context, $data);
    }

    public function getCartTotal()
    {
        $cartAmount = 0.00;
        $bnplDefaultAmount = "cartAmount";
        $quoteId = $this->getCheckoutSession()->getQuoteId();
        if (empty($quoteId)) {
            return 0.00;
        }
        $cartAmount = $this->_quoteRepository->get($quoteId)->getGrandTotal();

        $amount = array_key_exists('amount', $this->getData()) && !empty($this->getData('amount')) ? $this->getData('amount') : $bnplDefaultAmount;
        $cartAmount = $amount == "cartAmount" || !is_numeric($amount) ? $cartAmount : $amount;
        return $cartAmount;
    }

    public function getToggleColor()
    {
        $bnplDefaultSwithcerColor = "#3A3CA6";
        $color = array_key_exists('color', $this->getData()) && !empty($this->getData('color')) ? $this->getData('color') : $bnplDefaultSwithcerColor;
        return $color;
    }
    
    public function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }
}
