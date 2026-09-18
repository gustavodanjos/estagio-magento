<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Api\Data;

/**
 * Product review data contract.
 *
 * @api
 */
interface ReviewInterface
{
    public const REVIEW_ID = 'review_id';
    public const PRODUCT_ID = 'product_id';
    public const AUTHOR = 'author';
    public const COMMENT = 'comment';
    public const RATING = 'rating';
    public const IS_APPROVED = 'is_approved';
    public const CREATED_AT = 'created_at';

    /**
     * @return int|null
     */
    public function getReviewId(): ?int;

    /**
     * @param int $reviewId
     * @return $this
     */
    public function setReviewId(int $reviewId);

    /**
     * @return int
     */
    public function getProductId(): int;

    /**
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId);

    /**
     * @return string
     */
    public function getAuthor(): string;

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor(string $author);

    /**
     * @return string
     */
    public function getComment(): string;

    /**
     * @param string $comment
     * @return $this
     */
    public function setComment(string $comment);

    /**
     * @return int
     */
    public function getRating(): int;

    /**
     * @param int $rating
     * @return $this
     */
    public function setRating(int $rating);

    /**
     * @return bool
     */
    public function isApproved(): bool;

    /**
     * @param bool $isApproved
     * @return $this
     */
    public function setIsApproved(bool $isApproved);

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt);
}
