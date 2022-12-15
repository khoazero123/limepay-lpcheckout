<?php

namespace Limepay\Lpcheckout\Block\Widget;

use Magento\Framework\View\Element\Template;

class LpWidget extends Template
{
    protected $_cookieManager;
    protected $_cookieMetadataFactory;
    protected $_sessionManager;
    protected $_pricingHelper;
    private $product;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_sessionManager = $sessionManager;
        $this->_pricingHelper = $pricingHelper;
        parent::__construct($context, $data);
    }

    public function getInstallmentAmount($amount)
    {
        return $amount/4;
    }

    public function getFormattedPrice($amount)
    {
        return $this->_pricingHelper->currency($amount, true, false);
    }

    public function getCookie($cookieName)
    {
        return $this->_cookieManager->getCookie($cookieName);
    }

    public function setCookie($cookieName, $value, $duration = 86400)
    {
        $metadata = $this->_cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($this->_sessionManager->getCookiePath())
            ->setDomain($this->_sessionManager->getCookieDomain());

        return $this->_cookieManager->setPublicCookie(
            $cookieName,
            $value,
            $metadata
        );
    }
    
    public function deleteCookie($cookieName)
    {
        $metadata = $this->_cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setPath($this->_sessionManager->getCookiePath())
            ->setDomain($this->_sessionManager->getCookieDomain());
        return $this->_cookieManager->deleteCookie($cookieName, $metadata);
    }
}
