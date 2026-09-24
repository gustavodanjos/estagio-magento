<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Rating implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $options = [];
        for ($rating = 1; $rating <= 5; $rating++) {
            $options[] = ['value' => $rating, 'label' => (string)$rating];
        }
        return $options;
    }
}
