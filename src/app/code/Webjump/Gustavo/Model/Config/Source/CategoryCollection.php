<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\Config\Source;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class CategoryCollection implements OptionSourceInterface
{
    private const INDENT = '   ';
    private const MAX_DEPTH = 20;

    public function __construct(
        private readonly CategoryFactory $categoryFactory,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [
            ['value' => '', 'label' => __('-- Informar o link manualmente --')],
        ];

        foreach ($this->loadTree() as ['category' => $category, 'depth' => $depth]) {
            $label = $depth > 0
                ? str_repeat(self::INDENT, $depth) . $category->getName()
                : $category->getName();

            $options[] = [
                'value' => (string)$category->getId(),
                'label' => __($label),
            ];
        }

        return $options;
    }

    private function loadTree(): array
    {
        $store = $this->storeManager->getStore();
        $rootCategoryId = (int)$store->getRootCategoryId();
        $rootCategory = $this->categoryFactory->create()->load($rootCategoryId);

        $collection = $this->categoryFactory->create()->getCollection();
        $collection->addAttributeToSelect(['name', 'parent_id', 'path']);
        $collection->addAttributeToSort('name');
        $collection->setStoreId((int)$store->getId());
        $collection->addIsActiveFilter();
        $collection->addFieldToFilter(
            'path',
            ['like' => ($rootCategory->getPath() ?: $rootCategoryId) . '/%']
        );

        $childrenByParent = [];
        foreach ($collection as $category) {
            $childrenByParent[(int)$category->getParentId()][] = $category;
        }

        return $this->flatten($childrenByParent, $rootCategoryId, 0);
    }
    private function flatten(array $childrenByParent, int $parentId, int $depth): array
    {
        if (!isset($childrenByParent[$parentId]) || $depth > self::MAX_DEPTH) {
            return [];
        }

        $branch = [];
        foreach ($childrenByParent[$parentId] as $category) {
            $branch[] = ['category' => $category, 'depth' => $depth];
            $branch = array_merge(
                $branch,
                $this->flatten($childrenByParent, (int)$category->getId(), $depth + 1)
            );
        }

        return $branch;
    }
}
