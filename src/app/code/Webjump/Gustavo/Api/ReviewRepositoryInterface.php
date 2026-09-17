<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Api\Data\ReviewSearchResultsInterface;

/**
 * Product review CRUD contract.
 *
 * @api
 */
interface ReviewRepositoryInterface
{
    /**
     * @param ReviewInterface $review
     * @return ReviewInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(ReviewInterface $review): ReviewInterface;

    /**
     * @param int $reviewId
     * @return ReviewInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $reviewId): ReviewInterface;

    /**
     * @param ReviewInterface $review
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(ReviewInterface $review): bool;

    /**
     * @param int $reviewId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $reviewId): bool;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return ReviewSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReviewSearchResultsInterface;
}
