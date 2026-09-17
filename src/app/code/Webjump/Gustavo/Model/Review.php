<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model;

use Magento\Framework\Model\AbstractModel;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Model\ResourceModel\Review as ReviewResource;

class Review extends AbstractModel implements ReviewInterface
{
    protected $_idFieldName = self::REVIEW_ID;

    protected function _construct(): void
    {
        $this->_init(ReviewResource::class);
    }

    public function getReviewId(): ?int
    {
        $reviewId = $this->getData(self::REVIEW_ID);
        return $reviewId === null ? null : (int)$reviewId;
    }

    public function setReviewId(int $reviewId)
    {
        return $this->setData(self::REVIEW_ID, $reviewId);
    }

    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getAuthor(): string
    {
        return (string)$this->getData(self::AUTHOR);
    }

    public function setAuthor(string $author)
    {
        return $this->setData(self::AUTHOR, $author);
    }

    public function getComment(): string
    {
        return (string)$this->getData(self::COMMENT);
    }

    public function setComment(string $comment)
    {
        return $this->setData(self::COMMENT, $comment);
    }

    public function getRating(): int
    {
        return (int)$this->getData(self::RATING);
    }

    public function setRating(int $rating)
    {
        return $this->setData(self::RATING, $rating);
    }

    public function isApproved(): bool
    {
        return (bool)$this->getData(self::IS_APPROVED);
    }

    public function setIsApproved(bool $isApproved)
    {
        return $this->setData(self::IS_APPROVED, $isApproved);
    }

    public function getCreatedAt(): ?string
    {
        $createdAt = $this->getData(self::CREATED_AT);
        return $createdAt === null ? null : (string)$createdAt;
    }

    public function setCreatedAt(string $createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
