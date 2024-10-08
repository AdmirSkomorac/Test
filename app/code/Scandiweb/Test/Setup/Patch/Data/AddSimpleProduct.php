<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\State;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class AddSimpleProduct implements DataPatchInterface
{
    /**
     * @var string
     */
    private const SKU = 'simple-product';

    /**
     * @var string
     */
    private const PRODUCT_NAME = 'Simple Product';

     /**
     * @var float
     */
    private const PRICE = 50.00;

    /**
     * @var int
     */
    private const CATEGORY_ID = 2;

    /**
     * Default stock quantity for the product.
     * 
     * @var int
     */
    private const QTY = 100;

    /**
     * Quantity for the product in the inventory source.
     * 
     * This quantity represents how much stock will be available in the default inventory source.
     * 
     * @var int
     */
    private const SOURCE_QTY = 100;

    /**
     * @var ProductInterfaceFactory
     */
    protected ProductInterfaceFactory $productInterfaceFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var CategoryFactory
     */
    protected CategoryFactory $categoryFactory;

    /**
     * @var State
     */
    protected State $state;

    /**
     * @var SourceItemInterfaceFactory
     */
    protected SourceItemInterfaceFactory $sourceItemFactory;
    
    /**
     * @var SourceItemsSaveInterface
     */
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;
   
    /**
     * @var EavSetup
     */
    protected EavSetup $eavSetup;

    /**
     * Constructor for AddSimpleProduct.
     * Initializes the product factory, product repository, category factory, and application state.
     *
     * @param ProductInterfaceFactory $productInterfaceFactory Factory for creating product instances
     * @param ProductRepositoryInterface $productRepository Repository for saving and retrieving products
     * @param CategoryFactory $categoryFactory Factory for creating category instances
     * @param State $state Application state, used to set the area code
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param EavSetup $eavSetup
     */
    public function __construct(
        ProductInterfaceFactory $productInterfaceFactory,
        ProductRepositoryInterface $productRepository,
        CategoryFactory $categoryFactory,
        State $state,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        EavSetup $eavSetup
    ) {
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository = $productRepository;
        $this->categoryFactory = $categoryFactory;
        $this->state = $state;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->eavSetup = $eavSetup;
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
        $sku = self::SKU;

        // Check if the product already exists by SKU
        if ($this->productRepository->getIdBySku($sku)) {
            return; // If product exists, skip creation
        }

        $product = $this->productInterfaceFactory->create();
        
        // Use EavSetup to get the attribute set ID dynamically
        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');
        
        $product->setSku(self::SKU);
        $product->setName(self::PRODUCT_NAME);
        $product->setPrice(self::PRICE);
        $product->setAttributeSetId($attributeSetId); // Default attribute set for products
        $product->setStatus(Status::STATUS_ENABLED); // Enable product
        $product->setVisibility(Visibility::VISIBILITY_BOTH); // Catalog and Search
        $product->setTypeId(Type::TYPE_SIMPLE);
        $product->setStockData(['qty' => self::QTY, 'is_in_stock' => 1]);

        // Assign the product to the "Default Category" (ID 2)
        $categoryId = self::CATEGORY_ID;
        $category = $this->categoryFactory->create()->load($categoryId);
        $product->setCategoryIds([$category->getId()]);

        // Save the product with the category assignment
        $product = $this->productRepository->save($product);

        // Create and configure source item for inventory
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default'); // Set the default source code
        $sourceItem->setQuantity(self::SOURCE_QTY); // Set the quantity
        $sourceItem->setSku($product->getSku()); // Link the product SKU to the source item
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK); // Set stock status to "In Stock"

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
