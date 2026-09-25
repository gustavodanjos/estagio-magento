<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Block\Adminhtml\Review\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $reviewId = $this->getReviewId();
        if (!$reviewId) {
            return [];
        }

        return [
            'label' => __('Excluir avaliação'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s');",
                __('Tem certeza de que deseja excluir esta avaliação?'),
                $this->getUrl('*/*/delete', ['review_id' => $reviewId])
            ),
            'sort_order' => 20,
        ];
    }
}
