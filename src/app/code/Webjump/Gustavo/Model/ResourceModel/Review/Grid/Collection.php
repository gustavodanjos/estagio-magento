<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\ResourceModel\Review\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Psr\Log\LoggerInterface as Logger;
use Webjump\Gustavo\Model\ResourceModel\Review as ReviewResource;
use Webjump\Gustavo\Model\ResourceModel\Review\Collection as ReviewCollection;

class Collection extends ReviewCollection implements SearchResultInterface
{
    private ?AggregationInterface $aggregations = null;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        string $mainTable = 'webjump_gustavo_review',
        ?string $eventPrefix = 'webjump_gustavo_review_grid_collection',
        ?string $eventObject = 'review_grid_collection',
        ?string $resourceModel = ReviewResource::class,
        ?string $model = Document::class,
        $connection = null,
        ?AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    public function getAggregations(): ?AggregationInterface
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations): void
    {
        $this->aggregations = $aggregations;
    }

    public function getSearchCriteria(): ?\Magento\Framework\Api\SearchCriteriaInterface
    {
        return null;
    }

    public function setSearchCriteria(?\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null): SearchResultInterface
    {
        return $this;
    }

    public function getTotalCount(): int
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount): SearchResultInterface
    {
        return $this;
    }

    public function setItems(?array $items = null): SearchResultInterface
    {
        return $this;
    }
}
