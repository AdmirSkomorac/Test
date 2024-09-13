<?php
namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\State;

class AddSimpleProduct implements DataPatchInterface
{
    private $productFactory;
    private $productRepository;
    private $categoryFactory;
    private $state;

    public function __construct(
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CategoryFactory $categoryFactory,
        State $state
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->categoryFactory = $categoryFactory;
        $this->state = $state;
    }

    public function apply()
    {
        // Set the area code to 'adminhtml'
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Area code might already be set, so catch the exception
        }

        $product = $this->productFactory->create();
        $product->setSku('simple-product');
        $product->setName('Simple Product');
        $product->setPrice(50);
        $product->setAttributeSetId(4); // Default attribute set for products
        $product->setStatus(1); // Enable product
        $product->setVisibility(4); // Catalog, Search
        $product->setTypeId('simple');
        $product->setStockData(['qty' => 100, 'is_in_stock' => 1]);

        // Save the product
        $this->productRepository->save($product);

        // Assign the product to the "Default Category" (ID 2)
        $categoryId = 2;
        $category = $this->categoryFactory->create()->load($categoryId);
        $product->setCategoryIds([$category->getId()]);

        // Save the product with the category assignment
        $this->productRepository->save($product);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public static function getVersion()
    {
        return '1.0.0';
    }
}