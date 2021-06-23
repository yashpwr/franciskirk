<?php

namespace Cminds\MultiUserAccounts\Controller\Permission;

use Magento\Framework\App\Action\Action as ActionController;
use Magento\Framework\Controller\ResultFactory;

/**
 * Cminds MultiUserAccounts permission redirect controller.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Redirect extends ActionController
{
    /**
     * Redirect user.
     *
     * @return ResultFactory
     */
    public function execute()
    {
        $uenc = $this->getRequest()->getParam('uenc');
        if ($uenc !== null) {
            $uenc = base64_decode($uenc);
        } else {
            $uenc = $this->_url->getUrl();
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setUrl($uenc);

        return $resultRedirect;
    }
}
