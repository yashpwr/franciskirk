<?php
/**
 * Blueskytechco
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Blueskytechco.com license that is
 * available through the world-wide-web at this URL:
 * http://www.blueskytechco.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Blueskytechco
 * @package    Rokanthemes_Brand
 * @copyright  Copyright (c) 2014 Blueskytechco (http://www.blueskytechco.com/)
 * @license    http://www.blueskytechco.com/LICENSE-1.0.html
 */
namespace Rokanthemes\Brand\Model;

use Magento\Framework\DataObject\IdentityInterface;

/**
 * Brand Model
 */
class Brand extends \Magento\Framework\Model\AbstractModel
{	
	/**
	 * Brand's Statuses
	 */
	const STATUS_ENABLED = 1;
	const STATUS_DISABLED = 0;

    /**
     * Product collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /**
     * URL Model instance
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_url;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_brandHelper;

    /**
     * @param \Magento\Framework\Model\Context                          $context                  
     * @param \Magento\Framework\Registry                               $registry                           
     * @param \Rokanthemes\Brand\Model\ResourceModel\Brand|null                      $resource                 
     * @param \Rokanthemes\Brand\Model\ResourceModel\Brand\Collection|null           $resourceCollection       
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory 
     * @param \Magento\Store\Model\StoreManagerInterface                $storeManager             
     * @param \Magento\Framework\UrlInterface                           $url                      
     * @param \Rokanthemes\Brand\Helper\Data                                    $brandHelper              
     * @param array                                                     $data                     
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Rokanthemes\Brand\Model\ResourceModel\Brand $resource = null,
        \Rokanthemes\Brand\Model\ResourceModel\Brand\Collection $resourceCollection = null,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $url,
        \Rokanthemes\Brand\Helper\Data $brandHelper,
        array $data = []
        ) {
        $this->_storeManager = $storeManager;
        $this->_url = $url;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_brandHelper = $brandHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

	/**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Rokanthemes\Brand\Model\ResourceModel\Brand');
    }

    /**
     * Prepare page's statuses.
     * Available event cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * Check if page identifier exist for specific store
     * return page id if page exists
     *
     * @param string $identifier
     * @param int $storeId
     * @return int
     */
    public function checkIdentifier($identifier, $storeId)
    {
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }

    /**
     * Get category products collection
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getProductCollection()
    {
        $collection = $this->_productCollectionFactory->create()->addAttributeToSelect('*')->addAttributeToFilter('product_brand',array('eq'=>$this->getId()));
        return $collection;
    }

    public function getUrl()
    {
        $url = $this->_storeManager->getStore()->getBaseUrl();
        $route = $this->_brandHelper->getConfig('general_settings/route');
        $url_prefix = $this->_brandHelper->getConfig('general_settings/url_prefix');
        $urlPrefix = '';
        if($url_prefix){
            $urlPrefix = $url_prefix.'/';
        }
        $url_suffix = $this->_brandHelper->getConfig('general_settings/url_suffix');
        return $url.$urlPrefix.$this->getUrlKey().$url_suffix;
    }

    /**
     * Retrive image URL
     *
     * @return string
     */
    public function getImageUrl()
    {
        $url = false;
        $image = $this->getImage();
        if ($image) {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $image;
        };
        return $url;
    }

    public function loadByBrandName($brand_name = "") {
        if($brand_name) {
            $brand_id = $this->_getResource()->getBrandIdByName($brand_name);
            if($brand_id) {
                $this->load((int)$brand_id);
            }
        }
        return $this;
    }

    public function saveProduct($product_id = "0") {
        if($product_id) {
            $this->_getResource()->saveProduct($this, $product_id);
        }
        return $this;
    }

    /**
     * Retrive thumbnail URL
     *
     * @return string
     */
    public function getThumbnailUrl()
    {
        $url = false;
        $thumbnail = $this->getThumbnail();
        if ($thumbnail) {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $thumbnail;
        };
        return $url;
    }
}