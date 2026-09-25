<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ProductSelectActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!isset($item['entity_id'])) {
                    continue;
                }
                $item[$this->getData('name')] = [
                    'select' => [
                        'label' => __('Selecionar'),
                        'callback' => [
                            'target' => 'selectProduct',
                            'provider' => 'webjump_gustavo_review_form.webjump_gustavo_review_form.product_select_handler',
                        ],
                    ],
                ];
            }
        }

        return $dataSource;
    }
}
