<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Test\Integration\Model;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Api\Data\ReviewInterfaceFactory;
use Webjump\Gustavo\Api\ReviewRepositoryInterface;

#[DataFixture(ProductFixture::class, ['sku' => 'simple'], 'simple_product')]
class ReviewRepositoryTest extends TestCase
{
    private $reviewRepository;
    private $reviewFactory;
    private $searchCriteriaBuilder;
    private DataFixtureStorage $fixtures;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->reviewRepository = $objectManager->get(ReviewRepositoryInterface::class);
        $this->reviewFactory = $objectManager->get(ReviewInterfaceFactory::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    public function testSaveCreatesReview(): void
    {
        $productId = $this->getFixtureProductId();
        $review = $this->buildReview($productId);

        $savedReview = $this->reviewRepository->save($review);

        $this->assertNotNull($savedReview->getReviewId());
        $loadedReview = $this->reviewRepository->getById($savedReview->getReviewId());
        $this->assertSame($productId, $loadedReview->getProductId());
        $this->assertSame('Gustavo', $loadedReview->getAuthor());
        $this->assertSame(5, $loadedReview->getRating());
        $this->assertTrue($loadedReview->isApproved());
    }

    public function testSaveUpdatesReview(): void
    {
        $review = $this->reviewRepository->save($this->buildReview($this->getFixtureProductId()));

        $review->setAuthor('Maria');
        $review->setIsApproved(false);
        $this->reviewRepository->save($review);

        $loadedReview = $this->reviewRepository->getById($review->getReviewId());
        $this->assertSame('Maria', $loadedReview->getAuthor());
        $this->assertFalse($loadedReview->isApproved());
    }

    public function testGetByIdThrowsExceptionForMissingReview(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->reviewRepository->getById(999999);
    }


    public function testDeleteById(): void
    {
        $review = $this->reviewRepository->save($this->buildReview($this->getFixtureProductId()));

        $this->assertTrue($this->reviewRepository->deleteById($review->getReviewId()));
        $this->expectException(NoSuchEntityException::class);
        $this->reviewRepository->getById($review->getReviewId());
    }

    
    public function testGetListFiltersByProductIdAndApproved(): void
    {
        $productId = $this->getFixtureProductId();
        $this->reviewRepository->save($this->buildReview($productId, true, 'Filtro aprovado'));
        $this->reviewRepository->save($this->buildReview($productId, false, 'Filtro reprovado'));

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ReviewInterface::PRODUCT_ID, $productId)
            ->addFilter(ReviewInterface::IS_APPROVED, true)
            ->addFilter(ReviewInterface::COMMENT, 'Filtro %', 'like')
            ->create();

        $results = $this->reviewRepository->getList($searchCriteria);

        $this->assertSame(1, $results->getTotalCount());
        $items = $results->getItems();
        $this->assertSame('Filtro aprovado', reset($items)->getComment());
    }

    private function buildReview(int $productId, bool $approved = true, string $comment = 'Ótimo produto!'): ReviewInterface
    {
        $review = $this->reviewFactory->create();
        $review->setProductId($productId);
        $review->setAuthor('Gustavo');
        $review->setComment($comment);
        $review->setRating(5);
        $review->setIsApproved($approved);

        return $review;
    }

    private function getFixtureProductId(): int
    {
        $product = $this->fixtures->get('simple_product');

        return (int)$product->getId();
    }
}
