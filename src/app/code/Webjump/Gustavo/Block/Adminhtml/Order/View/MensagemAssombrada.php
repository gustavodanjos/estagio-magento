<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Webjump\Gustavo\Model\MensagemAssombrada as MensagemAssombradaModel;

class MensagemAssombrada extends Template
{
    private Registry $registry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    public function getOrder(): ?object
    {
        return $this->registry->registry('current_order');
    }

    public function getMensagem(): ?string
    {
        $order = $this->getOrder();

        if ($order === null) {
            return null;
        }

        return MensagemAssombradaModel::normalize(
            $order->getData(MensagemAssombradaModel::FIELD_CODE)
        );
    }
}
