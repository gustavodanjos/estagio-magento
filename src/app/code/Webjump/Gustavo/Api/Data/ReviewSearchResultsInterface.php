<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results for product reviews.
 *
 * @api
 */
interface ReviewSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Webjump\Gustavo\Api\Data\ReviewInterface[]
     */
    public function getItems();

    /**
     * @param \Webjump\Gustavo\Api\Data\ReviewInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
