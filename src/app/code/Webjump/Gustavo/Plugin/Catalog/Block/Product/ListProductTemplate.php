<?php
declare(strict_types=1);

namespace Webjump\Gustavo\Plugin\Catalog\Block\Product;

class ListProductTemplate
{
    public function afterGetTemplate(\Magento\Catalog\Block\Product\ListProduct $subject, $result)
    {
        if ($subject->getNameInLayout() === 'category.products.list') {
            return 'Webjump_Gustavo::product/list.phtml';
        }
        return $result;
    }
}
