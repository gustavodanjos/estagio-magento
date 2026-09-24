<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\ReviewExport;

use Magento\Ui\Controller\Adminhtml\Export\GridToCsv as BaseGridToCsv;

class GridToCsv extends BaseGridToCsv
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review_export';

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
