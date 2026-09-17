<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Api\Data\ReviewSearchResultsInterface;
use Webjump\Gustavo\Api\Data\ReviewSearchResultsInterfaceFactory;
use Webjump\Gustavo\Api\ReviewRepositoryInterface;
use Webjump\Gustavo\Model\ResourceModel\Review as ReviewResource;
use Webjump\Gustavo\Model\ResourceModel\Review\CollectionFactory;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function __construct(
        private readonly ReviewResource $reviewResource,
        private readonly ReviewFactory $reviewFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReviewSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(ReviewInterface $review): ReviewInterface
    {
        if (!$review instanceof Review) {
            throw new CouldNotSaveException(
                __('The review must be an instance of %1.', Review::class)
            );
        }

        try {
            $this->reviewResource->save($review);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save the review: %1', $exception->getMessage()));
        }

        return $review;
    }

    public function getById(int $reviewId): ReviewInterface
    {
        $review = $this->reviewFactory->create();
        $this->reviewResource->load($review, $reviewId);

        if (!$review->getId()) {
            throw new NoSuchEntityException(__('Review with id "%1" does not exist.', $reviewId));
        }

        return $review;
    }

    public function delete(ReviewInterface $review): bool
    {
        try {
            $this->reviewResource->delete($review);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete the review: %1', $exception->getMessage()));
        }

        return true;
    }

    public function deleteById(int $reviewId): bool
    {
        return $this->delete($this->getById($reviewId));
    }

    public function getList(SearchCriteriaInterface $searchCriteria): ReviewSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var ReviewSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
