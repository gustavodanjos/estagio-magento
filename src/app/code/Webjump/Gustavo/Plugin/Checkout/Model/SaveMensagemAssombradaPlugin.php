<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Plugin\Checkout\Model;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Webjump\Gustavo\Model\MensagemAssombrada;

class SaveMensagemAssombradaPlugin
{
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository
    ) {
    }

    /**
     * @param int|string $cartId
     */
    public function afterSaveAddressInformation(
        ShippingInformationManagement $subject,
        PaymentDetailsInterface $paymentDetails,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): PaymentDetailsInterface {
        $value = $this->extractValue($addressInformation);

        if ($value !== null && mb_strlen($value) > MensagemAssombrada::MAX_LENGTH) {
            throw new LocalizedException(
                __('The message must not exceed %1 characters.', MensagemAssombrada::MAX_LENGTH)
            );
        }

        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setData(MensagemAssombrada::FIELD_CODE, $value);
        $this->quoteRepository->save($quote);

        return $paymentDetails;
    }

    private function extractValue(ShippingInformationInterface $addressInformation): ?string
    {
        $address = $addressInformation->getShippingAddress();
        $extensionAttributes = $address ? $address->getExtensionAttributes() : null;

        if ($extensionAttributes === null) {
            return null;
        }

        return MensagemAssombrada::normalize(
            $extensionAttributes->getWebjumpGustavoMensagem()
        );
    }
}
