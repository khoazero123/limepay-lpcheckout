<?php

namespace Limepay\Lpcheckout\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class TitleSettingOptions implements ArrayInterface
{
    const TEXT_ONLY = 'text_only';
    const IMAGE_ONLY = 'image_only';
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => __('Show title with cards image'),
            ],
            [
                'value' => self::TEXT_ONLY,
                'label' => __('Show title only')
            ],
            [
                'value' => self::IMAGE_ONLY,
                'label' => __('Show cards image only')
            ]
        ];
    }
}
