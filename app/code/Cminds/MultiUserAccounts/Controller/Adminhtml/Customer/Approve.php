<?php

namespace Cminds\MultiUserAccounts\Controller\Adminhtml\Customer;

use Cminds\MultiUserAccounts\Model\Config;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\AuthorizationInterface;

class Approve extends Action
{
    /**
     * @var Config
     */
    protected $moduleConfig;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @param Context $context
     * @param Config $moduleConfig
     * @param AuthorizationInterface $authorization
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        Context $context,
        Config $moduleConfig,
        AuthorizationInterface $authorization,
        CustomerRepository $customerRepository
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->authorization = $authorization;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->authorization->isAllowed(
            'Magento_Customer::manage'
        );
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $customerId = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->moduleConfig->isEnabled() || !$this->_isAllowed() || !$customerId) {
            $resultRedirect->setPath('404');
        } else {
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('customer_is_active', 1);
            $this->customerRepository->save($customer);
            $this->messageManager->addSuccessMessage(__('Customer activated successfully.'));
            $resultRedirect->setPath('customer/index/edit', ['id' => $customerId]);
        }
        return $resultRedirect;
    }
}
