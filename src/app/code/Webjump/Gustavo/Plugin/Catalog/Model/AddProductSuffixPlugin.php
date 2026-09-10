<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Plugin\Catalog\Model;

use Magento\Catalog\Model\Product;

class AddProductSuffixPlugin
{
    private const SUFFIX = ' - Destaque Webjump';

    
    private const WEBJUMP_CATEGORY_ID = 3;

    public function afterGetName(Product $subject, ?string $result): ?string
    {
        if ($result === null || $result === '' || str_contains($result, self::SUFFIX)) {
            return $result;
        }

        if (!in_array(self::WEBJUMP_CATEGORY_ID, $subject->getCategoryIds())) {
            return $result;
        }

        return $result . self::SUFFIX;
    }
}
