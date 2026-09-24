<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Webjump\Gustavo\Api\ReviewRepositoryInterface;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review';

    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $reviewId = (int)$this->getRequest()->getParam('review_id');
        if ($reviewId) {
            try {
                $this->reviewRepository->deleteById($reviewId);
                $this->messageManager->addSuccessMessage(__('Avaliação excluída.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->messageManager->addErrorMessage(__('Não encontramos a avaliação para excluir.'));
        return $resultRedirect->setPath('*/*/');
    }
}
