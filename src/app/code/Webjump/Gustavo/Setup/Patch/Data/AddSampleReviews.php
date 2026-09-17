<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Setup\Patch\Data;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddSampleReviews implements DataPatchInterface
{
    private const TABLE = 'webjump_gustavo_review';
    private const CATEGORY_NAME = 'Webjump';
    private const FALLBACK_PRODUCT_IDS = [1, 2042];

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly CategoryCollectionFactory $categoryCollectionFactory
    ) {
    }

    public function apply(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);

        if ((int)$connection->fetchOne("SELECT COUNT(*) FROM {$table}") > 0) {
            return;
        }

        $productIds = $this->resolveProductIds();
        $rows = $this->buildSampleRows($productIds);

        $connection->insertMultiple($table, $rows);
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * @throws LocalizedException
     */
    private function resolveProductIds(): array
    {
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToFilter('name', self::CATEGORY_NAME)->setPageSize(1);
        $category = $categoryCollection->getFirstItem();

        if ($category->getId()) {
            $productIds = $category->getProductCollection()->getAllIds();
            if (!empty($productIds)) {
                return array_map('intval', $productIds);
            }
        }

        if (!empty(self::FALLBACK_PRODUCT_IDS)) {
            return self::FALLBACK_PRODUCT_IDS;
        }

        throw new LocalizedException(__('No products found to link the sample reviews.'));
    }

    /**
     * @param int[] $productIds
     * @return array<int, array{product_id: int, author: string, comment: string, rating: int, is_approved: int}>
     */
    private function buildSampleRows(array $productIds): array
    {
        $samples = [
            [
                'author' => 'Maria Silva',
                'comment' => 'Produto excelente, superou minhas expectativas!',
                'rating' => 5,
                'is_approved' => 1,
            ],
            [
                'author' => 'João Pereira',
                'comment' => 'Boa qualidade, porém a entrega demorou um pouco.',
                'rating' => 4,
                'is_approved' => 1,
            ],
            [
                'author' => 'Ana Souza',
                'comment' => 'Produto razoável, mas o acabamento poderia ser melhor.',
                'rating' => 3,
                'is_approved' => 0,
            ],
            [
                'author' => 'Carlos Lima',
                'comment' => 'Não recomendo, chegou com defeito.',
                'rating' => 2,
                'is_approved' => 0,
            ],
            [
                'author' => 'Fernanda Costa',
                'comment' => 'Adorei! Voltarei a comprar com certeza.',
                'rating' => 5,
                'is_approved' => 1,
            ],
        ];

        $rows = [];
        $productCount = count($productIds);

        foreach ($samples as $index => $sample) {
            $sample['product_id'] = $productIds[$index % $productCount];
            $rows[] = $sample;
        }

        return $rows;
    }
}
