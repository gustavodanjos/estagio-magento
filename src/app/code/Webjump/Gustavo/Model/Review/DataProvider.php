<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\Review;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Model\ResourceModel\Review\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    private ?array $loadedData = null;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ProductRepositoryInterface $productRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        foreach ($this->collection->getItems() as $review) {
            $this->loadedData[$review->getId()] = $this->enrichWithProductLabel($review->getData());
        }

        $data = $this->dataPersistor->get('webjump_gustavo_review');
        $data = is_array($data) ? $data : [];
        if (!empty($data)) {
            $review = $this->collection->getNewEmptyItem();
            $review->setData($data);
            $reviewId = (int)($review->getId() ?: 0);
            $this->loadedData[$reviewId] = $this->enrichWithProductLabel($review->getData());
            $this->dataPersistor->clear('webjump_gustavo_review');
        }

        return $this->loadedData;
    }

    private function enrichWithProductLabel(array $data): array
    {
        $productId = (int)($data[ReviewInterface::PRODUCT_ID] ?? 0);
        $data['product_label'] = '';

        if ($productId) {
            try {
                $product = $this->productRepository->getById($productId);
                $data['product_label'] = sprintf('%s (SKU: %s) [#%s]', $product->getName(), $product->getSku(), $productId);
            } catch (NoSuchEntityException $exception) {
                $data['product_label'] = sprintf(__('Produto removido [#%s]'), $productId);
            }
        }

        return $data;
    }
}
