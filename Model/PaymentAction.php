<?php
namespace Limepay\Lpcheckout\Model;

use Limepay\Lpcheckout\Helper\Logger;

class PaymentAction
{
    public $_paymentAction;

    protected $_paymentMethodCode;

    const SESSION_PAYMENT_ACTION_PARAM_NAME = 'saved_payment_action';

    public function __construct(
        \Magento\Framework\Session\Generic $session
    ) {
        $this->session = $session;
    }

    public function init( $paymentMethodCode )
    {
        $this->_paymentMethodCode = $paymentMethodCode;
    }

    public function process( $paymentAction )
    {
        $this->validate($paymentAction);
        $this->_paymentAction = $paymentAction;
    }

    public function setPaymentAction( $paymentAction )
    {
        $this->_paymentAction = $paymentAction;
        $this->session->setData( $this->getSessionParamName(), json_encode( $paymentAction ) );
    }

    public function isPaymentActionRequired()
    {
        return ( isset( $this->_paymentAction ) && !empty( $this->_paymentAction ) );
    }

    private function validate( $paymentAction )
    {
        $savedPaymentAction = $this->fetchSavedPaymentAction();
        if ( isset( $savedPaymentAction ) && !empty( $savedPaymentAction ) ) {
            if ( $savedPaymentAction == $paymentAction ) {
                return true;
            }
        }
        $errorMsg = 'Limepay payment action validation failed:';
        \Limepay\Lpcheckout\Helper\Logger::log( $errorMsg );
        throw new \Magento\Framework\Exception\LocalizedException( __( $errorMsg ) );
    }

    private function fetchSavedPaymentAction()
    {
        $savedPaymentAction = $this->session->getData( $this->getSessionParamName() );
        if ( isset( $savedPaymentAction ) && !empty( $savedPaymentAction ) ) {
            return json_decode( $savedPaymentAction );
        }
        return null;
    }

    private function getSessionParamName( $paymentMethodCode = null )
    {
        $pMCode = $this->_paymentMethodCode;
        if ( isset( $paymentMethodCode ) ) {
            $pMCode = $paymentMethodCode;
        }
        return PaymentAction::SESSION_PAYMENT_ACTION_PARAM_NAME . '_' . $pMCode;
    }

    public function clearPaymentAction()
    {
        $this->session->setData( $this->getSessionParamName('lpcheckout'), null );
        $this->session->setData( $this->getSessionParamName('lpcheckout_paycard'), null );
        $this->session->setData( $this->getSessionParamName('lpcheckout_payplan'), null );
        $this->_paymentAction = null;
    }
}
