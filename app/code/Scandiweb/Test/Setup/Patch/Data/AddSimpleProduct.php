<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\State;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class AddSimpleProduct implements DataPatchInterface
{
    /**
     * Factory for creating product instances.
     *
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productInterfaceFactory;

    /**
     * Repository for saving and retrieving products.
     *
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * Factory for creating category instances.
     *
     * @var CategoryFactory
     */
    protected CategoryFactory $categoryFactory;

    /**
     * Application state, used to set the area code.
     *
     * @var State
     */
    protected State $state;

    /**
     * Factory for creating source item instances for inventory management.
     *
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItemFactory;
    
    /**
     * Interface for saving source items, used to manage product inventory across sources.
     *
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;

    /**
     * Constructor for AddSimpleProduct.
     * Initializes the product factory, product repository, category factory, and application state.
     *
     * @param ProductInterfaceFactory $productInterfaceFactory Factory for creating product instances
     * @param ProductRepositoryInterface $productRepository Repository for saving and retrieving products
     * @param CategoryFactory $categoryFactory Factory for creating category instances
     * @param State $state Application state, used to set the area code
     */
    public function __construct(
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        CategoryFactory $categoryFactory,
        State $state,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface
    ) {
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
        $this->categoryFactory = $categoryFactory;
        $this->state = $state;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
    }

    /**
     * Applies the data patch to create and save a simple product.
     * 
     * This method creates a new simple product, sets its attributes,
     * assigns it to the "Default Category", and saves it to the repository.
     * 
     * @return void
     */
    public function apply(): void
    {
        // Emulate the 'adminhtml' area code to ensure that the product creation process works correctly
        $this->state->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * Executes the product creation logic under the 'adminhtml' area code.
     *
     * @return void
     */
    public function execute(): void
    {
        $sku = 'simple-product';

        // Check if the product already exists by SKU
        try {
            $this->productRepository->get($sku);
            return; // If product exists, skip creation
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Proceed with product creation if the product doesn't exist
        }

        $product = $this->productInterfaceFactory->create();
        
        // Set attributes
        $attributeSetId = 4; // Use the default attribute set ID (this could be dynamic)
        
        $product->setSku($sku);
        $product->setName('Simple Product');
        $product->setPrice(50);
        $product->setAttributeSetId($attributeSetId); // Default attribute set for products
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED); // Enable product
        $product->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH); // Catalog and Search
        $product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $product->setStockData(['qty' => 100, 'is_in_stock' => 1]);

        // Assign the product to the "Default Category" (ID 2)
        $categoryId = 2;
        $category = $this->categoryFactory->create()->load($categoryId);
        $product->setCategoryIds([$category->getId()]);

        // Save the product with the category assignment
        $this->productRepository->save($product);

        // Create and configure source item for inventory
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default'); // Set the default source code
        $sourceItem->setQuantity(100); // Set the quantity
        $sourceItem->setSku($product->getSku()); // Link the product SKU to the source item
        $sourceItem->setStatus(\Magento\InventoryApi\Api\Data\SourceItemInterface::STATUS_IN_STOCK); // Set stock status to "In Stock"

        // Save the source item
        $this->sourceItemsSaveInterface->execute([$sourceItem]);
    }


    /**
     * Retrieves the list of class dependencies for this data patch.
     *
     * @return array An empty array if there are no dependencies.
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Retrieves the list of aliases for this data patch.
     *
     * @return array An empty array if there are no aliases.
     */
    public function getAliases(): array
    {
        return [];
    }
}
