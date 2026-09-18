<?php
declare(strict_types=1);

namespace Webjump\Gustavo\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ProductSeloSustentavel implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry
    ) {
    }

    public function getCurrentProduct(): ?ProductInterface
    {
        return $this->registry->registry('current_product');
    }

    public function hasSeloSustentavel(): bool
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return false;
        }

        $value = $product->getData('selo_sustentavel');
        return $value === '1' || $value === 1 || $value === true;
    }
}