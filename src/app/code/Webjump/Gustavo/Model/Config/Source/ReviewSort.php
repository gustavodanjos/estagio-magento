<?php

declare(strict_types=1);

namespace Webjump\Gustavo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Webjump\Gustavo\Api\Data\ReviewInterface;

class ReviewSort implements OptionSourceInterface
{
    public const CREATED_AT_DESC = 'created_at_desc';
    public const RATING_DESC = 'rating_desc';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::CREATED_AT_DESC, 'label' => __('Mais recentes')],
            ['value' => self::RATING_DESC, 'label' => __('Maior nota')],
        ];
    }

    public static function fieldFor(string $value): string
    {
        return $value === self::RATING_DESC ? ReviewInterface::RATING : ReviewInterface::CREATED_AT;
    }
}
