<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Block\Adminhtml\Review\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    public function __construct(
        private readonly Context $context
    ) {
    }

    public function getReviewId(): ?int
    {
        $reviewId = $this->context->getRequest()->getParam('review_id');
        return $reviewId === null ? null : (int)$reviewId;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
