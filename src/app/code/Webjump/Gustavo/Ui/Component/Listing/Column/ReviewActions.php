<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ReviewActions extends Column
{
    private const URL_PATH_EDIT = 'webjump_gustavo/review/edit';
    private const URL_PATH_DELETE = 'webjump_gustavo/review/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!isset($item['review_id'])) {
                    continue;
                }
                $item[$this->getData('name')] = [
                    'edit' => [
                        'href' => $this->urlBuilder->getUrl(
                            self::URL_PATH_EDIT,
                            ['review_id' => $item['review_id']]
                        ),
                        'label' => __('Editar'),
                    ],
                    'delete' => [
                        'href' => $this->urlBuilder->getUrl(
                            self::URL_PATH_DELETE,
                            ['review_id' => $item['review_id']]
                        ),
                        'label' => __('Excluir'),
                        'confirm' => [
                            'title' => __('Excluir avaliação %1', $item['review_id']),
                            'message' => __('Tem certeza de que deseja excluir a avaliação %1?', $item['review_id']),
                        ],
                        'post' => true,
                    ],
                ];
            }
        }

        return $dataSource;
    }
}
