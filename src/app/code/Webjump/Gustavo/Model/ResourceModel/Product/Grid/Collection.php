<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\ResourceModel\Product\Grid;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;

class Collection extends ProductCollection implements SearchResultInterface
{
    protected ?AggregationInterface $aggregations = null;

    protected function _construct(): void
    {
        $this->_init(Document::class, ProductResource::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->joinAttribute('name', 'catalog_product/name', 'entity_id');
        return $this;
    }

    protected function _prepareUrlDataObject()
    {
        return $this;
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(?SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount)
    {
        return $this;
    }

    public function setItems(?array $items = null)
    {
        return $this;
    }
}
