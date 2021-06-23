<?php

namespace Cminds\MultiUserAccounts\Controller\Plugin\Permission\Customer\Account\LoginPost;

use Cminds\MultiUserAccounts\Api\SubaccountRepositoryInterface;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * Cminds MultiUserAccounts customer account login post controller plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Plugin
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var SubaccountRepositoryInterface
     */
    private $subaccountRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Object initialization.
     *
     * @param ManagerInterface              $messageManager
     * @param ModuleConfig                  $moduleConfig
     * @param ResponseInterface             $response
     * @param UrlInterface                  $urlBuilder
     * @param SubaccountRepositoryInterface $subaccountRepository
     * @param CustomerRepositoryInterface   $customerRepository
     */
    public function __construct(
        ManagerInterface $messageManager,
        ModuleConfig $moduleConfig,
        ResponseInterface $response,
        UrlInterface $urlBuilder,
        SubaccountRepositoryInterface $subaccountRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->messageManager = $messageManager;
        $this->moduleConfig = $moduleConfig;
        $this->response = $response;
        $this->urlBuilder = $urlBuilder;
        $this->subaccountRepository = $subaccountRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Check if customer wants to login as subaccount.
     *
     * @param ActionInterface  $subject
     * @param RequestInterface $request
     *
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeDispatch(
        ActionInterface $subject,
        RequestInterface $request
    ) {
        if ($this->moduleConfig->isEnabled() === false) {
            return null;
        }

        if ($request->isPost() === false) {
            return null;
        }

        $login = $request->getPost('login');
        if (empty($login['username']) || empty($login['password'])) {
            return null;
        }

        try {
            /** @var CustomerInterface $customer */
            $customer = $this->customerRepository
                ->get($login['username']);

            /**
             * Check customer is active flag.
             */
            $parentIsActive = $customer->getCustomAttribute('customer_is_active');
            if ($parentIsActive !== null) {
                $parentIsActive = (int)$parentIsActive->getValue();
            } else {
                $parentIsActive = 1;
            }

            if ($parentIsActive === 0) {
                $subject->getActionFlag()->set('', 'no-dispatch', true);

                $this->messageManager->addErrorMessage(
                    __('Your account is not active.')
                );
                $this->response->setRedirect(
                    $this->urlBuilder->getUrl('customer/account/login')
                );
            }

            /**
             * If it is sub-account check its parent account is active flag.
             */
            if ($this->subaccountRepository->getByCustomerId($customer->getId())) {
                $subaccount = $this->subaccountRepository->getByCustomerId($customer->getId());

                $parentId = $subaccount->getParentCustomerId();

                /** @var CustomerInterface $parentCustomer */
                $parentCustomer = $this->customerRepository->getById($parentId);

                $parentIsActive = $parentCustomer->getCustomAttribute('customer_is_active');
                if ($parentIsActive !== null) {
                    $parentIsActive = (int)$parentIsActive->getValue();
                } else {
                    $parentIsActive = 1;
                }

                if ($parentIsActive === 0 || (int)$subaccount->getIsActive() === 0) {
                    $subject->getActionFlag()->set('', 'no-dispatch', true);

                    $this->messageManager->addErrorMessage(
                        __('Your account or parent account is not active.')
                    );
                    $this->response->setRedirect(
                        $this->urlBuilder->getUrl('customer/account/login')
                    );
                }
            }
        } catch (NoSuchEntityException $e) {
            // No action is required here.
        }

        return null;
    }
}
