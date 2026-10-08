<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Webjump\Gustavo\Model\MensagemAssombrada;

class AddMensagemAssombradaPlugin
{
    private const SORT_ORDER = 200;

    public function afterProcess(
        LayoutProcessor $subject,
        array $jsLayout
    ): array {
        if (!isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children'])
        ) {
            return $jsLayout;
        }

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children'][MensagemAssombrada::FIELD_CODE] = [
                'component' => 'Magento_Ui/js/form/element/textarea',
                'config' => [
                    'customScope' => 'shippingAddress',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/textarea',
                ],
                'dataScope' => 'shippingAddress.extension_attributes.' . MensagemAssombrada::FIELD_CODE,
                'label' => __('Haunted message'),
                'tooltip' => [
                    'description' => __('Tell us a special detail about this order: a birthday, delivery preferences, or a note for our team.'),
                ],
                'provider' => 'checkoutProvider',
                'sortOrder' => self::SORT_ORDER,
                'validation' => [
                    'max_text_length' => MensagemAssombrada::MAX_LENGTH,
                ],
                'options' => [],
                'filterBy' => null,
                'customEntry' => null,
                'visible' => true,
            ];

        return $jsLayout;
    }
}
