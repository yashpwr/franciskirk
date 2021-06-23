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
namespace Rokanthemes\Brand\Block;

class Brandpage extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Rokanthemes\Brand\Helper\Data
     */
    protected $_brandHelper;

    /**
     * @var \Rokanthemes\Brand\Model\Brand
     */
    protected $_brand;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context      
     * @param \Magento\Framework\Registry                      $registry     
     * @param \Rokanthemes\Brand\Helper\Data                           $brandHelper  
     * @param \Rokanthemes\Brand\Model\Brand                           $brand        
     * @param \Magento\Store\Model\StoreManagerInterface       $storeManager 
     * @param array                                            $data         
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Rokanthemes\Brand\Helper\Data $brandHelper,
        \Rokanthemes\Brand\Model\Brand $brand,
        array $data = []
        ) {
        $this->_brand = $brand;
        $this->_coreRegistry = $registry;
        $this->_brandHelper = $brandHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        if(!$this->getConfig('general_settings/enable')) return;
        parent::_construct();
        $itemsperpage = (int)$this->getConfig('brand_list_page/item_per_page',12);
        $brand = $this->_brand;
        $brandCollection = $brand->getCollection()
        ->addFieldToFilter('status',1)
        ->setOrder('position','ASC');
        $this->setCollection($brandCollection);

		$template = 'brandlistpage_grid.phtml';
        if(!$this->hasData('template')){
            $this->setTemplate($template);
        }
    }

	/**
     * Prepare breadcrumbs
     *
     * @param \Magento\Cms\Model\Page $brand
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function _addBreadcrumbs()
    {
        $breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs');
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        $brandRoute = $this->_brandHelper->getConfig('general_settings/route');
        $page_title = $this->_brandHelper->getConfig('brand_list_page/page_title');

        if($breadcrumbsBlock){

        $breadcrumbsBlock->addCrumb(
            'home',
            [
            'label' => __('Home'),
            'title' => __('Go to Home Page'),
            'link' => $baseUrl
            ]
            );
        $breadcrumbsBlock->addCrumb(
            'rokanthemesbrand',
            [
            'label' => $page_title,
            'title' => $page_title,
            'link' => ''
            ]
            );
        }
    }

    /**
     * Set brand collection
     * @param \Rokanthemes\Brand\Model\Brand
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;
        return $this->_collection;
    }

    /**
     * Retrive brand collection
     * @param \Rokanthemes\Brand\Model\Brand
     */
    public function getCollection()
    {
        return $this->_collection;
    }

    public function getConfig($key, $default = '')
    {
        $result = $this->_brandHelper->getConfig($key);
        if(!$result){

            return $default;
        }
        return $result;
    }

    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $page_title = $this->getConfig('brand_list_page/page_title');
        $meta_description = $this->getConfig('brand_list_page/meta_description');
        $meta_keywords = $this->getConfig('brand_list_page/meta_keywords');
        $this->_addBreadcrumbs();
        $this->pageConfig->addBodyClass('rokanthemes-brandlist');
        if($page_title){
            $this->pageConfig->getTitle()->set($page_title);   
        }
        if($meta_keywords){
            $this->pageConfig->setKeywords($meta_keywords);   
        }
        if($meta_description){
            $this->pageConfig->setDescription($meta_description);   
        }
        return parent::_prepareLayout();
    }

    /**
     * Retrieve Toolbar block
     *
     * @return \Magento\Catalog\Block\Product\ProductList\Toolbar
     */
    public function getToolbarBlock()
    {
        $block = $this->getLayout()->getBlock('rokanthemesbrand_toolbar');
        if ($block) {
            $block->setDefaultOrder("position");
            $block->removeOrderFromAvailableOrders("price");
            return $block;
        }
    }

    /**
     * Need use as _prepareLayout - but problem in declaring collection from
     * another block (was problem with search result)
     * @return $this
     */
    // protected function _beforeToHtml()
    // {
        // $collection = $this->getCollection();
        // $toolbar = $this->getToolbarBlock();

        // set collection to toolbar and apply sort
        // if($toolbar){
            // $itemsperpage = (int)$this->getConfig('brand_list_page/item_per_page',12);
            // $toolbar->setData('_current_limit',$itemsperpage)->setCollection($collection);
            // $this->setChild('toolbar', $toolbar);
        // }
        // return parent::_beforeToHtml();
    // }
}