<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Test\Unit\Plugin\Checkout\Model;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webjump\Gustavo\Model\MensagemAssombrada;
use Webjump\Gustavo\Plugin\Checkout\Model\SaveMensagemAssombradaPlugin;

class SaveMensagemAssombradaPluginTest extends TestCase
{
    private const CART_ID = 42;

    private SaveMensagemAssombradaPlugin $plugin;
    /** @var CartRepositoryInterface&MockObject */
    private CartRepositoryInterface $quoteRepository;
    /** @var ShippingInformationManagement&MockObject */
    private ShippingInformationManagement $subject;
    /** @var PaymentDetailsInterface&MockObject */
    private PaymentDetailsInterface $paymentDetails;

    protected function setUp(): void
    {
        $this->quoteRepository = $this->createMock(CartRepositoryInterface::class);
        $this->plugin = new SaveMensagemAssombradaPlugin($this->quoteRepository);
        $this->subject = $this->createMock(ShippingInformationManagement::class);
        $this->paymentDetails = $this->createMock(PaymentDetailsInterface::class);
    }

    public function testPersistsTrimmedMensagemOnQuote(): void
    {
        $quote = $this->quoteWithMensagemExpectation('Boo!');
        $this->quoteRepository->expects($this->once())->method('save')->with($quote);

        $result = $this->plugin->afterSaveAddressInformation(
            $this->subject,
            $this->paymentDetails,
            self::CART_ID,
            $this->shippingInformationWith('  Boo!  ')
        );

        $this->assertSame($this->paymentDetails, $result);
    }

    public function testPersistsNullWhenMensagemIsEmpty(): void
    {
        $quote = $this->quoteWithMensagemExpectation(null);
        $this->quoteRepository->expects($this->once())->method('save')->with($quote);

        $this->plugin->afterSaveAddressInformation(
            $this->subject,
            $this->paymentDetails,
            self::CART_ID,
            $this->shippingInformationWith('   ')
        );
    }

    public function testPersistsNullWhenExtensionAttributesAreMissing(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getExtensionAttributes')->willReturn(null);

        $information = $this->createMock(ShippingInformationInterface::class);
        $information->method('getShippingAddress')->willReturn($address);

        $quote = $this->quoteWithMensagemExpectation(null);
        $this->quoteRepository->expects($this->once())->method('save')->with($quote);

        $this->plugin->afterSaveAddressInformation(
            $this->subject,
            $this->paymentDetails,
            self::CART_ID,
            $information
        );
    }

    public function testAcceptsMensagemWithMaximumLength(): void
    {
        $value = str_repeat('a', MensagemAssombrada::MAX_LENGTH);
        $quote = $this->quoteWithMensagemExpectation($value);
        $this->quoteRepository->expects($this->once())->method('save')->with($quote);

        $this->plugin->afterSaveAddressInformation(
            $this->subject,
            $this->paymentDetails,
            self::CART_ID,
            $this->shippingInformationWith($value)
        );
    }

    public function testRejectsMensagemLongerThanMaximumLength(): void
    {
        $value = str_repeat('a', MensagemAssombrada::MAX_LENGTH + 1);
        $this->quoteRepository->expects($this->never())->method('getActive');
        $this->quoteRepository->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The message must not exceed 255 characters.');

        $this->plugin->afterSaveAddressInformation(
            $this->subject,
            $this->paymentDetails,
            self::CART_ID,
            $this->shippingInformationWith($value)
        );
    }

    /**
     * @return Quote&MockObject
     */
    private function quoteWithMensagemExpectation(?string $expectedValue): Quote&MockObject
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())
            ->method('setData')
            ->with(MensagemAssombrada::FIELD_CODE, $expectedValue);
        $this->quoteRepository->method('getActive')->with(self::CART_ID)->willReturn($quote);

        return $quote;
    }

    private function shippingInformationWith(string $extensionAttributeValue): ShippingInformationInterface
    {
        $extensionAttributes = new class ($extensionAttributeValue) {
            public function __construct(private readonly string $value)
            {
            }

            public function getWebjumpGustavoMensagem(): string
            {
                return $this->value;
            }
        };

        $address = $this->createMock(AddressInterface::class);
        $address->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $information = $this->createMock(ShippingInformationInterface::class);
        $information->method('getShippingAddress')->willReturn($address);

        return $information;
    }
}
