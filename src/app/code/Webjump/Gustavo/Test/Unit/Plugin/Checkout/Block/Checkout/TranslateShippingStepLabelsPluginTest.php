<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Test\Unit\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webjump\Gustavo\Plugin\Checkout\Block\Checkout\TranslateShippingStepLabelsPlugin;

class TranslateShippingStepLabelsPluginTest extends TestCase
{
    private TranslateShippingStepLabelsPlugin $plugin;
    /** @var LayoutProcessor&MockObject */
    private LayoutProcessor $subject;

    protected function setUp(): void
    {
        $this->plugin = new TranslateShippingStepLabelsPlugin();
        $this->subject = $this->createMock(LayoutProcessor::class);
    }

    public function testTranslatesAddressFieldLabels(): void
    {
        $result = $this->plugin->afterProcess($this->subject, $this->buildLayoutWithFieldset());

        $children = $this->getChildren($result);

        $this->assertSame('Cidade', (string)$children['city']['label']);
        $this->assertSame('País', (string)$children['country_id']['label']);
        $this->assertSame('Estado/Província', (string)$children['region_id']['label']);
        $this->assertSame('CEP', (string)$children['postcode']['label']);
    }

    public function testKeepsUntouchedFieldsAndComponentConfig(): void
    {
        $result = $this->plugin->afterProcess($this->subject, $this->buildLayoutWithFieldset());

        $children = $this->getChildren($result);

        $this->assertSame('Primeiro Nome', (string)$children['firstname']['label']);
        $this->assertSame('Magento_Ui/js/form/element/abstract', $children['city']['component']);
        $this->assertSame('shippingAddress.city', $children['city']['dataScope']);
    }

    public function testReturnsLayoutUnchangedWhenFieldsetIsMissing(): void
    {
        $result = $this->plugin->afterProcess($this->subject, []);

        $this->assertSame([], $result);
    }

    private function getChildren(array $jsLayout): array
    {
        return $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children'];
    }

    private function buildLayoutWithFieldset(): array
    {
        return [
            'components' => [
                'checkout' => [
                    'children' => [
                        'steps' => [
                            'children' => [
                                'shipping-step' => [
                                    'children' => [
                                        'shippingAddress' => [
                                            'children' => [
                                                'shipping-address-fieldset' => [
                                                    'children' => [
                                                        'firstname' => [
                                                            'component' => 'Magento_Ui/js/form/element/abstract',
                                                            'label' => 'Primeiro Nome',
                                                        ],
                                                        'city' => [
                                                            'component' => 'Magento_Ui/js/form/element/abstract',
                                                            'dataScope' => 'shippingAddress.city',
                                                            'label' => 'City',
                                                        ],
                                                        'country_id' => [
                                                            'component' => 'Magento_Ui/js/form/element/select',
                                                            'label' => 'Country',
                                                        ],
                                                        'region_id' => [
                                                            'component' => 'Magento_Ui/js/form/element/region',
                                                            'label' => 'State/Province',
                                                        ],
                                                        'postcode' => [
                                                            'component' => 'Magento_Ui/js/form/element/post-code',
                                                            'label' => 'Zip/Postal Code',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
