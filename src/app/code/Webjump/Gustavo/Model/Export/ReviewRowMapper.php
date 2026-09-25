<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\Export;

use DateTime;
use DateTimeZone;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Webjump\Gustavo\Api\Data\ReviewInterface;

class ReviewRowMapper
{
    private const PRODUCT_NAME_ATTRIBUTE_CODE = 'name';

    private const DATE_FORMAT = 'd/m/Y H:i';

    private const EMPTY_DATE = '0000-00-00 00:00:00';

    private const HEADERS = [
        'ID',
        'ID do Produto',
        'Produto',
        'Autor',
        'Comentário',
        'Nota',
        'Aprovado',
        'Criado em',
    ];

    private array $productNames = [];

    public function __construct(
        private readonly TimezoneInterface $localeDate,
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return self::HEADERS;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function getRows(SearchResultInterface $searchResult): array
    {
        $items = $searchResult->getItems() ?: [];
        $this->loadProductNames($items);

        $rows = [];
        foreach ($items as $item) {
            $productId = (int)$item->getData(ReviewInterface::PRODUCT_ID);
            $rows[] = [
                (string)$item->getData(ReviewInterface::REVIEW_ID),
                (string)$productId,
                $this->productNames[$productId] ?? '',
                (string)$item->getData(ReviewInterface::AUTHOR),
                (string)$item->getData(ReviewInterface::COMMENT),
                (string)$item->getData(ReviewInterface::RATING),
                (string)$item->getData(ReviewInterface::IS_APPROVED) === '1' ? 'Sim' : 'Não',
                $this->formatDate((string)$item->getData(ReviewInterface::CREATED_AT)),
            ];
        }

        return $rows;
    }

    private function formatDate(string $value): string
    {
        if ($value === '' || $value === self::EMPTY_DATE) {
            return '';
        }

        try {
            $date = $this->localeDate->date(new DateTime($value, new DateTimeZone('UTC')));

            return $date->format(self::DATE_FORMAT);
        } catch (\Exception $exception) {
            return $value;
        }
    }

    private function loadProductNames(array $items): void
    {
        $productIds = array_unique(array_filter(
            array_map(
                static fn ($item): int => (int)$item->getData(ReviewInterface::PRODUCT_ID),
                $items
            )
        ));

        if (!$productIds) {
            return;
        }

        $attributeId = (int)$this->eavConfig->getAttribute(
            'catalog_product',
            self::PRODUCT_NAME_ATTRIBUTE_CODE
        )->getId();

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['p' => $connection->getTableName('catalog_product_entity')], ['entity_id'])
            ->joinLeft(
                ['v' => $connection->getTableName('catalog_product_entity_varchar')],
                'v.entity_id = p.entity_id AND v.attribute_id = ' . $attributeId . ' AND v.store_id = 0',
                ['name' => 'v.value']
            )
            ->where('p.entity_id IN (?)', $productIds);

        foreach ($connection->fetchAll($select) as $row) {
            $this->productNames[(int)$row['entity_id']] = (string)$row['name'];
        }
    }
}