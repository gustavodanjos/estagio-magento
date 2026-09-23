<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Webjump\Gustavo\Model\ReviewFactory;
use Webjump\Gustavo\Model\ResourceModel\Review as ReviewResource;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review';

    public function __construct(
        Context $context,
        private readonly ReviewFactory $reviewFactory,
        private readonly ReviewResource $reviewResource
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $reviewId = (int)$this->getRequest()->getParam('review_id');
        if ($reviewId) {
            try {
                $review = $this->reviewFactory->create();
                $this->reviewResource->load($review, $reviewId);
                $this->reviewResource->delete($review);
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
