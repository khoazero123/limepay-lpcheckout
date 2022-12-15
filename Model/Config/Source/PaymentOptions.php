<?php

namespace Limepay\Lpcheckout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentOptions implements ArrayInterface
{
    const PAYCARD = 'paycard';
    const PAYPLAN = 'payplan';
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => __('Full payment & split payment'),
            ],
            [
                'value' => self::PAYCARD,
                'label' => __('Full payment only')
            ],
            [
                'value' => self::PAYPLAN,
                'label' => __('Split payment only')
            ]
        ];
    }
}
