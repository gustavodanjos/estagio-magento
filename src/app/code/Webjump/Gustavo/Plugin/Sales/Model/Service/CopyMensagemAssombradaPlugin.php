<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Plugin\Sales\Model\Service;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\OrderService;
use Webjump\Gustavo\Model\MensagemAssombrada;

class CopyMensagemAssombradaPlugin
{
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository
    ) {
    }

    public function beforePlace(
        OrderService $subject,
        OrderInterface $order
    ): array {
        $quoteId = $order->getQuoteId();

        if ($quoteId !== null && $quoteId !== false) {
            $quote = $this->quoteRepository->get($quoteId);
            $order->setData(
                MensagemAssombrada::FIELD_CODE,
                MensagemAssombrada::normalize($quote->getData(MensagemAssombrada::FIELD_CODE))
            );
        }

        return [$order];
    }
}
