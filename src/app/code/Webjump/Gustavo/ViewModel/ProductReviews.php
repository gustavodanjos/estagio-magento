<?php

declare(strict_types=1);

namespace Webjump\Gustavo\ViewModel;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Api\ReviewRepositoryInterface;
use Webjump\Gustavo\Model\Config\ReviewsConfig;

class ProductReviews implements ArgumentInterface
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly ReviewsConfig $reviewsConfig
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->reviewsConfig->isEnabled();
    }

    public function getTitle(): string
    {
        return $this->reviewsConfig->getTitle();
    }

    /**
     * @return ReviewInterface[]
     */
    public function getApprovedReviews(int $productId): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $sortOrder = $this->sortOrderBuilder
            ->setField($this->reviewsConfig->getSortField())
            ->setDescendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ReviewInterface::PRODUCT_ID, $productId)
            ->addFilter(ReviewInterface::IS_APPROVED, true)
            ->addSortOrder($sortOrder)
            ->setPageSize($this->reviewsConfig->getItemsPerPage())
            ->create();

        return $this->reviewRepository->getList($searchCriteria)->getItems();
    }
}
