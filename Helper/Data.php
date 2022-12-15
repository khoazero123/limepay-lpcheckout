<?php
namespace Limepay\Lpcheckout\Helper;

use Exception;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\App\Helper\AbstractHelper as CoreHelper;

class Data extends CoreHelper
{
    protected $_cookieManager;
    protected $_cookieMetadataFactory;
    protected $_sessionManager;
    protected $_pricingHelper;
    private $product;
    
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        PricingHelper $pricingHelper,
        Context $context
    ) {
        $this->_cookieManager = $cookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_sessionManager = $sessionManager;
        $this->_pricingHelper = $pricingHelper;
        parent::__construct($context);
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