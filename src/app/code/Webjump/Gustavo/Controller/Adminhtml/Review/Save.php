<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Webjump\Gustavo\Api\Data\ReviewInterface;
use Webjump\Gustavo\Api\Data\ReviewInterfaceFactory;
use Webjump\Gustavo\Api\ReviewRepositoryInterface;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review';

    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly ReviewInterfaceFactory $reviewFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->extractRequestData();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $reviewId = (int)($data[ReviewInterface::REVIEW_ID] ?? 0);

        try {
            $this->validateRequestData($data);

            $review = $reviewId
                ? $this->reviewRepository->getById($reviewId)
                : $this->reviewFactory->create();

            $review->setProductId((int)$data[ReviewInterface::PRODUCT_ID]);
            $review->setAuthor(trim((string)$data[ReviewInterface::AUTHOR]));
            $review->setComment(trim((string)($data[ReviewInterface::COMMENT] ?? '')));
            $review->setRating((int)$data[ReviewInterface::RATING]);
            $review->setIsApproved((bool)($data[ReviewInterface::IS_APPROVED] ?? false));

            $this->reviewRepository->save($review);
            $this->messageManager->addSuccessMessage(__('Avaliação salva.'));
            $this->dataPersistor->clear('webjump_gustavo_review');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['review_id' => $review->getReviewId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error('Erro ao salvar avaliação: ' . $exception->getMessage(), ['exception' => $exception]);
            $this->messageManager->addErrorMessage(
                __('Não foi possível salvar a avaliação. Verifique os dados e tente novamente.')
            );
        }

        $this->dataPersistor->set('webjump_gustavo_review', $data);

        return $resultRedirect->setPath(
            $reviewId ? '*/*/edit' : '*/*/newaction',
            $reviewId ? ['review_id' => $reviewId] : []
        );
    }

    private function extractRequestData(): array
    {
        $params = $this->getRequest()->getParams();
        $data = $params['data'] ?? [];

        if (is_array($data) && $data) {
            return $data;
        }

        $fields = [
            ReviewInterface::REVIEW_ID,
            ReviewInterface::PRODUCT_ID,
            ReviewInterface::AUTHOR,
            ReviewInterface::COMMENT,
            ReviewInterface::RATING,
            ReviewInterface::IS_APPROVED,
        ];

        return array_intersect_key($params, array_flip($fields));
    }

    private function validateRequestData(array $data): void
    {
        $productId = (int)($data[ReviewInterface::PRODUCT_ID] ?? 0);
        if (!$productId) {
            throw new LocalizedException(
                __('Selecione um produto nas sugestões da busca ou no grid antes de salvar.')
            );
        }

        try {
            $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(__('O produto selecionado não existe mais.'));
        }

        if (!trim((string)($data[ReviewInterface::AUTHOR] ?? ''))) {
            throw new LocalizedException(__('Informe o autor da avaliação.'));
        }

        $rating = (int)($data[ReviewInterface::RATING] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new LocalizedException(__('A nota deve ser entre 1 e 5.'));
        }
    }
}
