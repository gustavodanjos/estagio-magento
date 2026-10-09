<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Observer;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webjump\Gustavo\Model\Attribute\Source\ProductSelo;

class AddProductSeloToListCollection implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        /** @var Collection $collection */
        $collection = $observer->getEvent()->getData('collection');

        if (!$collection instanceof Collection) {
            return;
        }

        $collection->addAttributeToSelect(ProductSelo::ATTRIBUTE_CODE);
    }
}