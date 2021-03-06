<?php
/**
* Copyright © 2015 tokitheme.com. All rights reserved.

* @author Blue Sky Team <contact@tokitheme.com>
*/

namespace Rokanthemes\Testimonial\Block\Adminhtml\Testimo\Edit;

/**
 * Adminhtml locator edit form block
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic {
	protected function _prepareForm() {
		/** @var \Magento\Framework\Data\Form $form */
		$form = $this->_formFactory->create(
			array(
				'data' => array(
					'id' => 'edit_form',
					'action' => $this->getUrl('*/*/save', ['store' => $this->getRequest()->getParam('store')]),
					'method' => 'post',
					'enctype' => 'multipart/form-data',
				),
			)
		);
		$form->setUseContainer(true);
		$this->setForm($form);
		return parent::_prepareForm();
	}
}
