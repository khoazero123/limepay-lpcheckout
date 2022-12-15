<?php

namespace Limepay\Lpcheckout\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Catalog\Helper\Data as TaxHelper;

class ProductPriceBnplToggle extends Template implements BlockInterface
{
    protected $_template = "widget/limepay_product_price_toggle.phtml";
    protected $_productloader;
    protected $_registry;
    protected $_lpHelper;
    protected $_taxHelper;
    private $product;
    private $userDefinedPriceCssClass = null;
    private $_defaultProductType = 'simple';

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\Registry $registry,
        \Limepay\Lpcheckout\Helper\Data $lpHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        TaxHelper $taxHelper,
        array $data = []
    )
    {
        $this->_productloader = $_productloader;
        $this->_registry = $registry;
        $this->_lpHelper = $lpHelper;
        $this->_taxHelper = $taxHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = $this->_registry->registry('product');
        }
        return $this->product;
    }

    public function getProductPrice()
    {
        $_product = $this->getProduct();
        $productPrice = 0;
        $bnplDefaultAmt = "productPrice";
        if (!empty($_product)) {
            if ($this->getProductType() == 'grouped') {
                $productPrice = $this->getGroupedProductPrice($_product);
            } elseif ($this->getProductType() == 'bundle') {
                $productPrice = $this->getBundleProductPrice($_product);
            } else {
                $productPrice = $this->_taxHelper->getTaxPrice($_product, $_product->getFinalPrice(), true);
            }

        }
        $amount = array_key_exists('amount', $this->getData()) && !empty($this->getData('amount')) ? $this->getData('amount') : $bnplDefaultAmt;
        if (!empty($amount) &&
            $amount !== "productPrice" &&
            is_numeric($amount)
        ) {
            $this->userDefinedPriceCssClass = 'custom-defined-price';
            $productPrice = $amount;
        }
        return $productPrice;
    }

    public function getProductType()
    {
        $_product = $this->getProduct();
        if (!empty($_product)) {
            return $_product->getTypeId();
        }
        return $this->_defaultProductType;
    }

    public function getGroupedProductPrice($_product)
    {
        $associatedProducts = $_product->getTypeInstance(true)->getAssociatedProducts($_product);
        $minPrice = null;
        foreach( $associatedProducts as $associatedProduct ) {
            if ($minPrice == null || $minPrice >= $associatedProduct->getPrice()) {
                $minPrice = $associatedProduct->getPrice();
            }
        }
        return floatval($minPrice);
    }

    public function getBundleProductPrice($_product)
    {
        return $_product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
    }

    public function getUserDefinedPriceCssClass()
    {
        return $this->userDefinedPriceCssClass;
    }

    public function getPriceColor()
    {
        $bnplDefaultSwithcerColor = "#fa5402";
        $color = array_key_exists('price_color', $this->getData()) && !empty($this->getData('price_color')) ? $this->getData('price_color') : $bnplDefaultSwithcerColor;
        return $color;
    }

    public function getToggleColor()
    {
        $bnplDefaultSwithcerColor = "#3A3CA6";
        $color = array_key_exists('toggle_color', $this->getData()) && !empty($this->getData('toggle_color')) ? $this->getData('toggle_color') : $bnplDefaultSwithcerColor;
        return $color;
    }
}
