<?php
/**
 * Copyright © 2015 RokanThemes.com. All rights reserved.

 * @author RokanThemes Team <contact@rokanthemes.com>
 */

namespace Rokanthemes\Blog\Block\Sidebar;

/**
 * Blog sidebar widget trait
 */
trait Widget
{
    /**
     * Retrieve block sort order
     * @return int
     */
    public function getSortOrder()
    {
        if (!$this->hasData('sort_order')) {
            $this->setData('sort_order', $this->_scopeConfig->getValue(
                'mfblog/sidebar/'.$this->_widgetKey.'/sort_order', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ));
        }
        return (int) $this->getData('sort_order');
    }

    /**
     * Retrieve block html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_scopeConfig->getValue(
            'mfblog/sidebar/'.$this->_widgetKey.'/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return parent::_toHtml();
        }

        return '';
    }
}
