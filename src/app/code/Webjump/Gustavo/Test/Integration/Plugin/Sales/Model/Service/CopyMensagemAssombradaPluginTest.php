<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Test\Integration\Plugin\Sales\Model\Service;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\OrderService;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Webjump\Gustavo\Model\MensagemAssombrada;
use Webjump\Gustavo\Plugin\Sales\Model\Service\CopyMensagemAssombradaPlugin;

class CopyMensagemAssombradaPluginTest extends TestCase
{
    private CartRepositoryInterface $quoteRepository;
    private OrderService $orderService;
    private CopyMensagemAssombradaPlugin $plugin;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteRepository = $objectManager->get(CartRepositoryInterface::class);
        $this->orderService = $objectManager->get(OrderService::class);
        $this->plugin = $objectManager->get(CopyMensagemAssombradaPlugin::class);
    }

    public function testCopiesQuoteMensagemToOrderBeforePlace(): void
    {
        $order = $this->createOrderForQuote($this->createSavedQuote('Boo!'));

        [$order] = $this->plugin->beforePlace($this->orderService, $order);

        $this->assertSame('Boo!', $order->getData(MensagemAssombrada::FIELD_CODE));
    }

    public function testSetsNullWhenQuoteHasNoMensagem(): void
    {
        $order = $this->createOrderForQuote($this->createSavedQuote(null));

        [$order] = $this->plugin->beforePlace($this->orderService, $order);

        $this->assertNull($order->getData(MensagemAssombrada::FIELD_CODE));
    }

    public function testKeepsOrderUntouchedWhenQuoteIdIsMissing(): void
    {
        $order = Bootstrap::getObjectManager()->create(Order::class);
        $order->setData(MensagemAssombrada::FIELD_CODE, 'existing');

        [$order] = $this->plugin->beforePlace($this->orderService, $order);

        $this->assertSame('existing', $order->getData(MensagemAssombrada::FIELD_CODE));
    }

    private function createSavedQuote(?string $mensagem): Quote
    {
        $quote = Bootstrap::getObjectManager()->create(Quote::class);
        $quote->setStoreId(1);
        $quote->setData(MensagemAssombrada::FIELD_CODE, $mensagem);
        $this->quoteRepository->save($quote);

        return $quote;
    }

    private function createOrderForQuote(Quote $quote): Order
    {
        $order = Bootstrap::getObjectManager()->create(Order::class);
        $order->setQuoteId((int)$quote->getId());

        return $order;
    }
}
