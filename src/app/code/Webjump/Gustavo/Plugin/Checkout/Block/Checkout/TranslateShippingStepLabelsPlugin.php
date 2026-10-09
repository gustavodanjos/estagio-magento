<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;

class TranslateShippingStepLabelsPlugin
{
    private const LABELS = [
        'city' => 'Cidade',
        'country_id' => 'País',
        'region_id' => 'Estado/Província',
        'postcode' => 'CEP',
    ];

    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        if (!isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'])) {
            return $jsLayout;
        }

        $fieldset = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];

        foreach (self::LABELS as $fieldCode => $label) {
            if (isset($fieldset[$fieldCode])) {
                $fieldset[$fieldCode]['label'] = $label;
            }
        }

        return $jsLayout;
    }
}
