<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Block\Adminhtml;

use Magento\Backend\Block\Widget\Button;

class AddNewReviewButton extends Button
{
    protected function _construct()
    {
        parent::_construct();
        $this->setData('id', 'webjump_gustavo_review_add_new');
        $this->setData('label', __('Nova avaliação'));
        $this->setData('class', 'add primary');
        $this->setData('on_click', sprintf("setLocation('%s')", $this->getUrl('webjump_gustavo/review/newAction')));
    }
}
