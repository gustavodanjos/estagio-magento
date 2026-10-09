<?php
declare(strict_types=1);

namespace Webjump\Gustavo\Model\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class ProductSelo extends AbstractSource
{
    public const ATTRIBUTE_CODE = 'selo_sustentavel';

    public const OPTION_NONE = '0';
    public const OPTION_SUSTENTAVEL = '1';
    public const OPTION_ASSOMBRADO = '2';

    public function getAllOptions(): array
    {
        return [
            ['label' => (string)__('Nenhum'), 'value' => self::OPTION_NONE],
            ['label' => (string)__('Selo Sustentável'), 'value' => self::OPTION_SUSTENTAVEL],
            ['label' => (string)__('Produto Assombrado'), 'value' => self::OPTION_ASSOMBRADO],
        ];
    }
}
