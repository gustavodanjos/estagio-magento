<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Test\Integration\Model\Export;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Api\Data\ReviewInterfaceFactory;
use Webjump\Gustavo\Api\ReviewRepositoryInterface;
use Webjump\Gustavo\Model\Export\ReviewRowMapper;
use Webjump\Gustavo\Model\ResourceModel\Review\Grid\Collection as GridCollection;
use Webjump\Gustavo\Model\ResourceModel\Review\Grid\CollectionFactory as GridCollectionFactory;

#[DataFixture(ProductFixture::class, ['sku' => 'export-simple', 'name' => 'Produto Exportavel'], 'export_product')]
class ReviewRowMapperTest extends TestCase
{
    private ReviewRowMapper $rowMapper;
    private ReviewRepositoryInterface $reviewRepository;
    private ReviewInterfaceFactory $reviewFactory;
    private GridCollectionFactory $gridCollectionFactory;
    private DataFixtureStorage $fixtures;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->rowMapper = $objectManager->get(ReviewRowMapper::class);
        $this->reviewRepository = $objectManager->get(ReviewRepositoryInterface::class);
        $this->reviewFactory = $objectManager->get(ReviewInterfaceFactory::class);
        $this->gridCollectionFactory = $objectManager->get(GridCollectionFactory::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    public function testHeadersContainProductAndBrazilianLabels(): void
    {
        $headers = $this->rowMapper->getHeaders();

        $this->assertSame(
            ['ID', 'ID do Produto', 'Produto', 'Autor', 'Comentário', 'Nota', 'Aprovado', 'Criado em'],
            $headers
        );
    }

    public function testApprovedReviewIsMappedToSimWithProductNameAndBrazilianDate(): void
    {
        $productId = $this->getFixtureProductId();
        $review = $this->reviewRepository->save($this->buildReview($productId, true));

        $rows = $this->rowMapper->getRows($this->getGridCollectionFor($review->getReviewId()));

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame((string)$review->getReviewId(), $row[0]);
        $this->assertSame((string)$productId, $row[1]);
        $this->assertSame('Produto Exportavel', $row[2]);
        $this->assertSame('Gustavo', $row[3]);
        $this->assertSame('Ótimo produto!', $row[4]);
        $this->assertSame('5', $row[5]);
        $this->assertSame('Sim', $row[6]);
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2}$#', $row[7]);
    }

    public function testDisapprovedReviewIsMappedToNao(): void
    {
        $review = $this->reviewRepository->save(
            $this->buildReview($this->getFixtureProductId(), false)
        );

        $rows = $this->rowMapper->getRows($this->getGridCollectionFor($review->getReviewId()));

        $this->assertCount(1, $rows);
        $this->assertSame('Não', $rows[0][6]);
    }

    private function getGridCollectionFor(int $reviewId): GridCollection
    {
        $collection = $this->gridCollectionFactory->create();
        $collection->addFieldToFilter(ReviewInterface::REVIEW_ID, $reviewId);

        return $collection;
    }

    private function buildReview(int $productId, bool $approved): ReviewInterface
    {
        $review = $this->reviewFactory->create();
        $review->setProductId($productId);
        $review->setAuthor('Gustavo');
        $review->setComment('Ótimo produto!');
        $review->setRating(5);
        $review->setIsApproved($approved);

        return $review;
    }

    private function getFixtureProductId(): int
    {
        $product = $this->fixtures->get('export_product');

        return (int)$product->getId();
    }
}