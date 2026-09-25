<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Webjump\Gustavo\Api\ReviewRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ReviewRepositoryInterface $reviewRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Page|Redirect
    {
        $reviewId = (int)$this->getRequest()->getParam('review_id');

        try {
            $review = $this->reviewRepository->getById($reviewId);
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addErrorMessage(__('Esta avaliação não existe mais.'));

            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webjump_Gustavo::review');
        $resultPage->getConfig()->getTitle()->prepend(__('Editar avaliação %1', $review->getReviewId()));
        return $resultPage;
    }
}
