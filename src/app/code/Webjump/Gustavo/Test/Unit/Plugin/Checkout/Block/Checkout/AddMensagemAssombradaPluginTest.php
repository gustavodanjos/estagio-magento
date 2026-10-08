<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Test\Unit\Plugin\Checkout\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webjump\Gustavo\Model\MensagemAssombrada;
use Webjump\Gustavo\Plugin\Checkout\Block\Checkout\AddMensagemAssombradaPlugin;

class AddMensagemAssombradaPluginTest extends TestCase
{
    private AddMensagemAssombradaPlugin $plugin;
    /** @var LayoutProcessor&MockObject */
    private LayoutProcessor $subject;

    protected function setUp(): void
    {
        $this->plugin = new AddMensagemAssombradaPlugin();
        $this->subject = $this->createMock(LayoutProcessor::class);
    }

    public function testAddsMensagemFieldToShippingAddressFieldset(): void
    {
        $result = $this->plugin->afterProcess($this->subject, $this->buildLayoutWithFieldset());

        $field = $this->getField($result);

        $this->assertSame('Magento_Ui/js/form/element/textarea', $field['component']);
        $this->assertSame(
            'shippingAddress.extension_attributes.' . MensagemAssombrada::FIELD_CODE,
            $field['dataScope']
        );
        $this->assertSame('shippingAddress', $field['config']['customScope']);
        $this->assertSame('ui/form/field', $field['config']['template']);
        $this->assertSame('ui/form/element/textarea', $field['config']['elementTmpl']);
        $this->assertSame(MensagemAssombrada::MAX_LENGTH, $field['validation']['max_text_length']);
        $this->assertSame('Haunted message', (string)$field['label']);
        $this->assertSame(
            'Tell us a special detail about this order: a birthday, delivery preferences, or a note for our team.',
            (string)$field['tooltip']['description']
        );
        $this->assertSame(200, $field['sortOrder']);
        $this->assertTrue($field['visible']);
    }

    public function testKeepsExistingFieldsetChildren(): void
    {
        $result = $this->plugin->afterProcess($this->subject, $this->buildLayoutWithFieldset());

        $children = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children'];

        $this->assertArrayHasKey('firstname', $children);
        $this->assertArrayHasKey(MensagemAssombrada::FIELD_CODE, $children);
    }

    public function testReturnsLayoutUnchangedWhenFieldsetIsMissing(): void
    {
        $result = $this->plugin->afterProcess($this->subject, []);

        $this->assertSame([], $result);
    }

    private function getField(array $jsLayout): array
    {
        return $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['shipping-address-fieldset']['children']
            [MensagemAssombrada::FIELD_CODE];
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
