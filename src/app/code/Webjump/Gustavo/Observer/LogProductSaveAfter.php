<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class LogProductSaveAfter implements ObserverInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        /** @var Product|null $product */
        $product = $observer->getEvent()->getProduct();

        if (!$product instanceof Product) {
            return;
        }

        $this->logger->info(sprintf(
            '[Webjump_Gustavo] Product saved successfully. ID: %s, SKU: %s, Name: "%s"',
            (string)$product->getId(),
            (string)$product->getSku(),
            (string)$product->getName()
        ));
    }
}
