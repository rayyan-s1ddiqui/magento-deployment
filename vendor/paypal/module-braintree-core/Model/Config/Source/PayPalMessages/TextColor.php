<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace PayPal\Braintree\Model\Config\Source\PayPalMessages;

use Magento\Framework\Data\OptionSourceInterface;

class TextColor implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'black', 'label' => __('black')],
            ['value' => 'white', 'label' => __('white')],
            ['value' => 'monochrome', 'label' => __('monochrome')],
            ['value' => 'grayscale', 'label' => __('grayscale')]
        ];
    }
}
