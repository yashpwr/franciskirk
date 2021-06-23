<?php

namespace Cminds\MultiUserAccounts\Plugin\Login;

use Cminds\MultiUserAccounts\Api\SubaccountRepositoryInterface;
use Cminds\MultiUserAccounts\Model\Config;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount;
use Exception;
use Magento\Customer\Controller\Account\LoginPost;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

class Auth
{
    private $config;
    private $customerRepository;
    private $subaccountRepository;
    private $subaccountResource;

    public function __construct(
        Config $config,
        CustomerRepository $customerRepository,
        SubaccountRepositoryInterface $subaccountRepository,
        Subaccount $subaccountResource
    ) {
        $this->config = $config;
        $this->customerRepository = $customerRepository;
        $this->subaccountRepository = $subaccountRepository;
        $this->subaccountResource = $subaccountResource;
    }

    public function beforeExecute(
        LoginPost $subject
    ) {
        if (!$this->config->isLoginAuthEnabled()) {
            return;
        }

        $request = $subject->getRequest();
        $originalParams = $request->getParams();
        $originalPost = $request->getPost();

        $loginParams = $request->getParam('login');
        $login = $loginParams['login'] ?? null;

        if (!$login) {
            return;
        }

        $parentEmail = $loginParams['username'];

        try {
            $parentCustomer = $this->customerRepository->get($parentEmail);
        } catch (Exception $e) {
            return;
        }

        $parentId = $parentCustomer->getId();

        $customerId = $this->subaccountResource->getSubaccountByParentIdAndLogin($parentId, $login);

        if (!$customerId) {
            return;
        }

        $targetCustomer = $this->customerRepository->getById($customerId);
        $loginParams['username'] = $targetCustomer->getEmail();
        $originalParams['login'] = $loginParams;
        $request->setParams($originalParams);
        $originalPost->set('login', $loginParams);
        $request->setPost($originalPost);
    }
}
