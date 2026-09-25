<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Webjump\Gustavo\Model\Config\Source\ReviewSort;

class ReviewsConfig
{
    private const XML_PATH_ENABLED = 'webjump_gustavo/reviews/enabled';
    private const XML_PATH_TITLE = 'webjump_gustavo/reviews/title';
    private const XML_PATH_ITEMS_PER_PAGE = 'webjump_gustavo/reviews/items_per_page';
    private const XML_PATH_DEFAULT_SORT = 'webjump_gustavo/reviews/default_sort';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getTitle(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_TITLE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getItemsPerPage(?int $storeId = null): int
    {
        $itemsPerPage = (int)$this->scopeConfig->getValue(self::XML_PATH_ITEMS_PER_PAGE, ScopeInterface::SCOPE_STORE, $storeId);
        return $itemsPerPage > 0 ? $itemsPerPage : 10;
    }

    public function getSortField(?int $storeId = null): string
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_DEFAULT_SORT, ScopeInterface::SCOPE_STORE, $storeId);
        return ReviewSort::fieldFor($value);
    }
}
