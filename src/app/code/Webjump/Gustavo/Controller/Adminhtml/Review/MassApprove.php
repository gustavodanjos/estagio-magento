<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Adminhtml\Review;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Webjump\Gustavo\Model\ResourceModel\Review\CollectionFactory;

class MassApprove extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webjump_Gustavo::review';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $approved = 0;
        foreach ($collection as $review) {
            $review->setIsApproved(true);
            $review->save();
            $approved++;
        }

        $this->messageManager->addSuccessMessage(__('Aprovamos %1 avaliação(ões).', $approved));

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
