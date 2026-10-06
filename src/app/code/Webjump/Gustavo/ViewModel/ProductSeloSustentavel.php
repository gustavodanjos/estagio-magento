<?php
declare(strict_types=1);

namespace Webjump\Gustavo\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Webjump\Gustavo\Model\Attribute\Source\ProductSelo;

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

    public function getSeloValue(): ?string
    {
        $product = $this->getCurrentProduct();
        if (!$product) {
            return null;
        }

        return self::normalize($product->getData(ProductSelo::ATTRIBUTE_CODE));
    }

    public static function normalize(mixed $value): ?string
    {
        $value = (string)$value;

        return in_array($value, [ProductSelo::OPTION_SUSTENTAVEL, ProductSelo::OPTION_ASSOMBRADO], true)
            ? $value
            : null;
    }
}
