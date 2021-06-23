<?php
/**
 * Copyright © 2015 RokanThemes.com. All rights reserved.

 * @author RokanThemes Team <contact@rokanthemes.com>
 */

namespace Rokanthemes\Blog\Block\Adminhtml\Import;

use Magento\Store\Model\ScopeInterface;

/**
 * Wordpress import block
 */
class Wordpress extends \Magento\Backend\Block\Widget\Form\Container
{

    /**
     * Initialize wordpress import block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Rokanthemes_Blog';
        $this->_controller = 'adminhtml_import';
        $this->_mode = 'wordpress';

        parent::_construct();

        if (!$this->_isAllowedAction('Rokanthemes_Blog::import')) {
            $this->buttonList->remove('save');
        } else {
          $this->updateButton(
              'save', 'label', __('Start Import')
          );
        }

        $this->buttonList->remove('delete');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get form save URL
     *
     * @see getFormActionUrl()
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/run', ['_current' => true]);
    }

}
