<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Block\Adminhtml\Review\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ResetButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Redefinir'),
            'class' => 'reset',
            'on_click' => "location.reload();",
            'sort_order' => 30,
        ];
    }
}
