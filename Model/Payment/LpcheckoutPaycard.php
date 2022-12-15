<?php

namespace Limepay\Lpcheckout\Model\Payment;

class LpcheckoutPaycard extends \Limepay\Lpcheckout\Model\Payment\Lpcheckout
{

    protected $_code                        = "lpcheckout_paycard";
    protected $_canUseCheckout              = false;
    protected $_canUseInternal              = false;

}
