<?php
namespace Limepay\Lpcheckout\Model;

use Limepay\Lpcheckout\Helper\Logger;
use Limepay\Lpcheckout\Helper\Generic;
use Limepay\Lpcheckout\Model\Config;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Helper\ImageFactory as ImageHelper;

class MerchantOrder
{
    public $_orderId;
    public $_merchantOrderId;

    const SESSION_ORDER_PARAM_NAME = 'saved_order';

    public function __construct(
        \Limepay\Lpcheckout\Helper\Api $api,
        \Limepay\Lpcheckout\Helper\Generic $helper,
        \Limepay\Lpcheckout\Model\Config $config,
        \Magento\Framework\Session\Generic $session,
        ProductFactory $productFactory,
        ImageHelper $imageHelper
    ) {
        $this->api            = $api;
        $this->helper         = $helper;
        $this->config         = $config;
        $this->session        = $session;
        $this->productFactory = $productFactory;
        $this->imageHelper    = $imageHelper;
    }

    public function create( $order )
    {
        $this->_orderId = $order->getIncrementId();
        $orderData = $this->getAlreadyCreatedOrder( $order );
        if ( !isset( $orderData ) || empty( $orderData ) ) {
            $orderParams = $this->buildRequest( $order );
            $resp = $this->api->createOrder( $orderParams );
            $this->_merchantOrderId = $this->processAPIResponse( $resp );

            $orderData = [
                'merchantOrderId' => $this->_merchantOrderId,
                'total' => $this->helper->getOrderGrandTotal( $order ),
                'orderId' => $this->_orderId
            ];
            $this->saveOrder( $orderData );
        } else {
            $this->_merchantOrderId = $orderData->merchantOrderId;
        }
    }

    private function getAlreadyCreatedOrder( $order )
    {
        $savedOrder = $this->fetchSavedOrder();
        if ( isset( $savedOrder ) && !empty( $savedOrder ) ) {
            if ( $savedOrder->orderId === $this->_orderId
                && $savedOrder->total == $this->helper->getOrderGrandTotal( $order ) ) {

                return $savedOrder;
            }
        }
        return null;
    }

    private function fetchSavedOrder()
    {
        $savedOrder = $this->session->getData( MerchantOrder::SESSION_ORDER_PARAM_NAME );
        if ( isset( $savedOrder ) && !empty( $savedOrder ) ) {
            return json_decode( $savedOrder );
        }
        return null;
    }

    private function saveOrder( $orderData )
    {
        $this->session->setData( MerchantOrder::SESSION_ORDER_PARAM_NAME, json_encode( $orderData ) );
    }

    public function clearOrder( )
    {
        $this->session->setData( MerchantOrder::SESSION_ORDER_PARAM_NAME, null );
        $this->_merchantOrderId = null;
    }

    private function processAPIResponse( $orderResp )
    {
        if ( array_key_exists( 'httpStatusCode', $orderResp ) ) {
            $orderStatusCode = $orderResp['httpStatusCode'];

            if ($orderStatusCode == 200) {
                return $orderResp['data']['merchantOrderId'];

            }else {
                $errorMsg = $this->helper->getErrorMessage( $orderResp, 'An unknown error occurred while creating the order with Limepay' );
                \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay order creation error: Unexpected httpStatusCode' );
                \Limepay\Lpcheckout\Helper\Logger::log( ['httpStatusCode' => $orderStatusCode, 'responseData' => $orderResp] );
                throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
            }
        }
        else {
            $errorMsg = $this->helper->getErrorMessage( $orderResp, 'An unknown error occurred while creating the order with Limepay' );
            \Limepay\Lpcheckout\Helper\Logger::log( 'Limepay order creation error: API call failed' );
            \Limepay\Lpcheckout\Helper\Logger::log( ['responseData' => $orderResp] );
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }
    }

    private function buildRequest( $order )
    {
        try {
            $total = $this->helper->getOrderGrandTotal( $order );
            $orderId = $this->_orderId;
            $currency = $order->getBaseCurrencyCode();
            $email = $order->getCustomerEmail();
            $items = [];

            $imageHelperObj = $this->imageHelper->create();
            $productLoader = $this->productFactory->create();

            foreach ( $order->getAllItems() as $item ) {
                if( $item->getData( 'has_children' ) ) {
                    continue;
                }
                $product = $productLoader->load( $item->getProductId() );
                $items[] = array(
                    'description'    => strval( $item->getName() ),
                    'sku'            => strval( $item->getSku() ),
                    'amount'         => $this->helper->convertToCents( $product->getFinalPrice() ),
                    'currency'       => $currency,
                    'quantity'       => $item->getQtyOrdered(),
                    'imageUrl'       => $imageHelperObj
                                        ->init( $product, 'product_small_image' )
                                        ->setImageFile( $product->getImage() )
                                        ->getUrl(),
                );
            }

            /* In case of virtual/downloadable/gift card products, get details from billing address */
            $shippingAddressArray = [];
            if ( $order->getShippingAddress() ) {
                $shippingAddressObject = $order->getShippingAddress();
                /* Get shipping address street lines */
                $shippingAddressStreetLine1 = null;
                $shippingAddressStreetLine2 = null;
                if ( is_array( $shippingAddressObject->getStreet() ) ) {
                    $shippingAddressStreetLine1 = array_key_exists( 0, $shippingAddressObject->getStreet() ) ? $shippingAddressObject->getStreet()[0] : null;
                    $shippingAddressStreetLine2 = array_key_exists( 1, $shippingAddressObject->getStreet() ) ? $shippingAddressObject->getStreet()[1] : null;
                } else {
                    $shippingAddressStreetLine1 = $shippingAddressObject->getStreet();
                }

                $shippingAddressArray = [
                    'amount' => $this->helper->convertToCents( $order->getShippingAmount() ),
                    'address' => [
                        'city' => strval( $shippingAddressObject->getCity() ),
                        'country' => strval( $shippingAddressObject->getCountryId() ),
                        'line1' => strval( $shippingAddressStreetLine1 ),
                        'line2' => strval( $shippingAddressStreetLine2 ),
                        'postalCode' => strval( $shippingAddressObject->getPostcode() ),
                        'state' => strval( $shippingAddressObject->getRegion() )
                    ],
                    'carrier' => strval( $order->getShippingDescription() ),
                    'phone' => strval( $shippingAddressObject->getTelephone() ),
                    'name' => strval( $shippingAddressObject->getName() )
                ];
            }

            /* Get billing address street lines */
            $billingAddressStreetLine1 = null;
            $billingAddressStreetLine2 = null;
            if ( is_array( $order->getBillingAddress()->getStreet() ) ) {
                $billingAddressStreetLine1 = array_key_exists( 0, $order->getBillingAddress()->getStreet() ) ? $order->getBillingAddress()->getStreet()[0] : null;
                $billingAddressStreetLine2 = array_key_exists( 1, $order->getBillingAddress()->getStreet() ) ? $order->getBillingAddress()->getStreet()[1] : null;
            } else {
                $billingAddressStreetLine1 = $order->getBillingAddress()->getStreet();
            }

            $description = ( count( $items ) > 0 ? strval( count( $items ) ) : 'No' ) . ' item(s)';
            $orderParams = [
                'internalOrderId' => strval( $orderId ),
                'paymentAmount'   => [ 'amount' => $total, 'currency' => $currency ],
                'customerEmail'   => $email ? $email : '',
                'items'           => $items,
                'description'     => $description,
                'discount'        => [
                    'amount' => $this->helper->convertToCents( $order->getDiscountAmount() * -1 )
                ],
                'billing' => [
                    'address' => [
                        'city' => strval( $order->getBillingAddress()->getCity() ),
                        'country' => strval( $order->getBillingAddress()->getCountryId() ),
                        'line1' => strval( $billingAddressStreetLine1 ),
                        'line2' => strval( $billingAddressStreetLine2 ),
                        'postalCode' => strval( $order->getBillingAddress()->getPostcode() ),
                        'state' => strval( $order->getBillingAddress()->getRegion() )
                    ],
                    'phone' => strval( $order->getBillingAddress()->getTelephone() ),
                    'name' => strval( $order->getBillingAddress()->getName() )
                ]
            ];
            if ( count( $shippingAddressArray ) ) {
                $orderParams['shipping'] = $shippingAddressArray;
            }

            return $orderParams;

        } catch ( \Exception $e ) {
            $msg = $e.getMessage();
            $errorMsg = 'Limepay API error: Error occured when generating order creation payload.';
            throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
        }
    }
}
