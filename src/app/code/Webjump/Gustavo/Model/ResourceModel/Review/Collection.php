<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\ResourceModel\Review;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Webjump\Gustavo\Model\Review;
use Webjump\Gustavo\Model\ResourceModel\Review as ReviewResource;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(Review::class, ReviewResource::class);
    }
}
