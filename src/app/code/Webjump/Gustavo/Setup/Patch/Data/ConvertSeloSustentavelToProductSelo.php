<?php
declare(strict_types=1);

namespace Webjump\Gustavo\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Webjump\Gustavo\Model\Attribute\Source\ProductSelo;

/**
 * Turns the boolean "Selo Sustentável" flag into a product seal picker.
 *
 * EavSetup::addAttribute is idempotent: it updates the existing attribute instead of creating a
 * new one, so the values already stored in catalog_product_entity_int are preserved. Option '1'
 * keeps meaning "Selo Sustentável" and option '2' is the new Halloween campaign seal, which is why
 * no data migration is needed.
 */
class ConvertSeloSustentavelToProductSelo implements DataPatchInterface, PatchRevertableInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            ProductSelo::ATTRIBUTE_CODE,
            $this->getAttributeConfig(ProductSelo::class, 'Selo do Produto')
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            ProductSelo::ATTRIBUTE_CODE,
            $this->getAttributeConfig(Boolean::class, 'Selo Sustentável')
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [AddSeloSustentavelAttribute::class];
    }

    private function getAttributeConfig(string $sourceModel, string $label): array
    {
        return [
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => $label,
            'input' => 'select',
            'source' => $sourceModel,
            'required' => false,
            'user_defined' => true,
            'default' => ProductSelo::OPTION_NONE,
        ];
    }
}
