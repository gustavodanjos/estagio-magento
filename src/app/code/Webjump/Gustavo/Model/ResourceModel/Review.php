<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Review extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('webjump_gustavo_review', 'review_id');
    }
}
