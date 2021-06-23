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
namespace Rokanthemes\Brand\Block\Widget;

class Brandlist extends AbstractWidget
{
    /**
     * Group Collection
     */
    protected $_brandCollection;

	/**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_brandHelper;

    /**
     * @var \Magento\Cms\Model\Block
     */
    protected $_blockModel;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context         
     * @param \Magento\Framework\Registry                      $registry        
     * @param \Rokanthemes\Brand\Helper\Data                           $brandHelper     
     * @param \Rokanthemes\Brand\Model\Brand                           $brandCollection 
     * @param array                                            $data            
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Rokanthemes\Brand\Helper\Data $brandHelper,
        \Rokanthemes\Brand\Model\Brand $brandCollection,
        \Magento\Cms\Model\Block $blockModel,
        array $data = []
        ) {
        $this->_brandCollection = $brandCollection;
        $this->_brandHelper = $brandHelper;
        $this->_coreRegistry = $registry;
        $this->_blockModel = $blockModel;
        parent::__construct($context, $brandHelper);
    }

    public function getCmsBlockModel(){
        return $this->_blockModel;
    }

    public function _toHtml()
    {
        if(!$this->_brandHelper->getConfig('general_settings/enable')) return;
        $this->setTemplate('widget/brand_list_owl.phtml');
        return parent::_toHtml();
    }

    public function getBrandCollection()
    {
        $number_item = $this->getConfig('number_item',12);
        $brandGroups = $this->getConfig('brand_groups');
        $collection = $this->_brandCollection->getCollection()
        ->addFieldToFilter('status',1);
        $brandGroups = explode(',', $brandGroups);
        if(is_array($brandGroups))
        {
            $collection->addFieldToFilter('group_id',array('in' => $brandGroups));
        }
        $collection->setPageSize($number_item)
        ->setCurPage(1)
        ->setOrder('position','ASC');
        return $collection;
    }
}