<?php
declare(strict_types=1);

namespace Webjump\Gustavo\Controller\Scare;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Webjump\Gustavo\CustomerData\ScareCounter as ScareCounterSection;

class Index implements HttpPostActionInterface
{
    public function __construct(
        private readonly Session $session,
        private readonly JsonFactory $jsonFactory,
    ) {
    }

    /**
     * Records a scare in the visitor session and returns the new total count.
     *
     * Returning JSON keeps every value out of the rendered HTML; the section
     * reload (triggered by sections.xml) delivers the updated counter to the
     * client without a full page refresh.
     */
    public function execute(): Json
    {
        $count = (int)$this->session->getData(ScareCounterSection::SESSION_KEY) + 1;
        $this->session->setData(ScareCounterSection::SESSION_KEY, $count);

        return $this->jsonFactory->create()->setData([
            'success' => true,
            'count' => $count,
        ]);
    }
}