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
use \Magento\Catalog\Block\Product\ProductList\Toolbar as BrandToolbar;

class Toolbar extends BrandToolbar
{
    /**
     * Render pagination HTML
     *
     * @return string
     */
    public function getPagerHtml()
    {
        $pagerBlock = $this->getChildBlock('brand_list_toolbar_pager');

        if ($pagerBlock instanceof \Magento\Framework\DataObject) {
            /* @var $pagerBlock \Magento\Theme\Block\Html\Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());

            $pagerBlock->setUseContainer(
                false
            )->setShowPerPage(
                false
            )->setShowAmounts(
                false
            )->setFrameLength(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setJump(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame_skip',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setLimit(
                $this->getLimit()
            )->setCollection(
                $this->getCollection()
            );
            return $pagerBlock->toHtml();
        }
        return '';
    }
}