<?php 
namespace Rokanthemes\ProductTab\Block;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject\IdentityInterface;

class Randompr extends \Magento\Catalog\Block\Product\AbstractProduct 
{
	/**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'Magento\Catalog\Block\Product\ProductList\Toolbar';

    /**
     * Product Collection
     *
     * @var AbstractCollection
     */
    protected $_productCollection;

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    protected $productCollectionFactory;
    protected $storeManager;
    protected $catalogConfig;
    protected $productVisibility;
    protected $scopeConfig;

    /**
     * @param Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\Product\Visibility $productVisibility,
        array $data = []
    ) {
        $this->_catalogLayer = $layerResolver->get();
        $this->_postDataHelper = $postDataHelper;
        $this->categoryRepository = $categoryRepository;
        $this->urlHelper = $urlHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $context->getStoreManager();
        $this->catalogConfig = $context->getCatalogConfig();
        $this->productVisibility = $productVisibility;
        parent::__construct(
            $context,
            $data
        );
    }
	public function getProducts()
    {
    	$storeId    = $this->storeManager->getStore()->getId();
		$category = $this->categoryRepository->get($this->_storeManager->getStore()->getRootCategoryId());
		$products = $category->getProductCollection();
		$products = $this->productCollectionFactory->create()->setStoreId($storeId);
		$products->joinField(
            'position',
            'catalog_category_product',
            'position',
            'product_id=entity_id',
            'category_id=' . (int)$category->getId()
        );
		$products
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite($category->getId())
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds());
		if(!$qty = $this->getConfig('qty'))
			$qty = 8;
		if($products->getSize() > $qty)
		{
			$prids = $products->getAllIds();
			$keys = array_rand($prids, $qty);
			$ids = array();
			foreach($keys as $key)
				$ids[] = $prids[$key];
			$products->addAttributeToFilter('entity_id', array('in'=>$ids));	
		}
        $products->setPageSize($qty)->setCurPage(1);
		$this->_eventManager->dispatch(
            'catalog_block_product_list_collection',
            ['collection' => $products]
        );
		return $products;
    }
}