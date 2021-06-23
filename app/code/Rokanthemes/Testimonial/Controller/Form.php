<?php 
/**
* Copyright © 2015 tokitheme.com. All rights reserved.

* @author Blue Sky Team <contact@tokitheme.com>
*/

namespace Rokanthemes\Testimonial\Controller;

abstract class Form extends \Magento\Framework\App\Action\Action {
	/**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

	/**
	 * A factory that knows how to create a "page" result
	 * Requires an instance of controller action in order to impose page type,
	 * which is by convention is determined from the controller action class
	 * @var \Magento\Framework\View\Result\PageFactory
	 */
	 
	/**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
    }
}
