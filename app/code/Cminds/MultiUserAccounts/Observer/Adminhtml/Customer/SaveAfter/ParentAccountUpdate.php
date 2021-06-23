<?php

namespace Cminds\MultiUserAccounts\Observer\Adminhtml\Customer\SaveAfter;

use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Cminds\MultiUserAccounts\Model\Service\Convert\Customer\ParentAccount as ParentAccountConverter;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount as SubaccountResource;
use Cminds\MultiUserAccounts\Model\Service\Assign\Account;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Cminds\MultiUserAccounts\Api\ParentaccountInterface;
use Cminds\MultiUserAccounts\Helper\Subaccount as HelperSubaccount;

/**
 * Cminds MultiUserAccounts after customer section configuration save observer.
 * Will be executed on "customer_save_after_subaccount_update"
 * event in admin area.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class ParentAccountUpdate implements ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     *
     */
    private $customerRepositoryInterface;

    /**
     * Module Config.
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Request object.
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Registry Object.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Parent Account Converter.
     *
     * @var ParentAccountConverter
     */
    private $parentAccountConverter;

    /**
     * Account Assigner.
     *
     * @var Account
     */
    private $accountAssigner;

    /**
     * Message Manager.
     *
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ParentaccountInterface
     */
    private $parentAccountInterface;

    /**
     * @var HelperSubaccount
     */
    private $HelperSubaccount;

    /**
     * @var SubaccountResource
     */
    private $SubaccountResource;

    /**
     * ParentAccountUpdate constructor.
     *
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param ModuleConfig $moduleConfig
     * @param RequestInterface $requestInterface
     * @param Registry $registry
     * @param ParentAccountConverter $parentAccountConverter
     * @param Account $accountAssigner
     * @param ManagerInterface $messageManager
     * @param ParentaccountInterface $parentAccountInterface
     * @param HelperSubaccount $helperSubaccount
     * @param SubaccountResource $subaccountResource
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        ModuleConfig $moduleConfig,
        RequestInterface $requestInterface,
        Registry $registry,
        ParentAccountConverter $parentAccountConverter,
        Account $accountAssigner,
        ManagerInterface $messageManager,
        ParentaccountInterface $parentAccountInterface,
        HelperSubaccount $helperSubaccount,
        SubaccountResource $subaccountResource
    ) {
        $this->customerRepositoryInterface  = $customerRepositoryInterface;
        $this->moduleConfig                 = $moduleConfig;
        $this->request                      = $requestInterface;
        $this->registry                     = $registry;
        $this->parentAccountConverter       = $parentAccountConverter;
        $this->accountAssigner              = $accountAssigner;
        $this->messageManager               = $messageManager;
        $this->parentAccountInterface       = $parentAccountInterface;
        $this->HelperSubaccount             = $helperSubaccount;
        $this->SubaccountResource           = $subaccountResource;
    }

    /**
     * Save parent customer id for the user after save on admin side.
     *
     * @param Observer $observer
     *
     * @return ParentAccountUpdate
     */
    public function execute(Observer $observer)
    {
        try {
            if ($this->moduleConfig->isEnabled() === false) {
                return $this;
            }

            $customerData = $this->getCustomerPostData();
            if (!$customerData) {
                return $this;
            }

            // keep the group_id synced between parent and subaccounts
            if ($this->moduleConfig->changeSubAccountGroup()) {
                $customerId         = $observer->getCustomer()->getId();
                $customerParentId   = $customerData['parent_account_id'];
                $subAccountIds      = $this->HelperSubaccount->getAllSubaccountIds($customerId);
                $hasSubAccounts     = (bool) count($subAccountIds);
                $accountsToUpdate   = array_merge($subAccountIds, [$customerId]);

                if (!empty($customerParentId) || $hasSubAccounts) {
                    $groupId = $customerData['group_id'];
                    if (!empty($customerParentId)) {
                        $groupId = $this->customerRepositoryInterface->getById($customerParentId)->getGroupId();
                    }

                    if ($hasSubAccounts) {
                        foreach ($accountsToUpdate as $accountToUpdate) {
                            $this->SubaccountResource->updateCustomerGroupId($accountToUpdate, $groupId);
                        }
                    }

                }
            }

            // check if it's the first time this function should be executed
            if (!$this->request->getParam('manageParentAccountIdDataFired')) {
                $customerId = (int)$observer->getCustomer()->getId();
                $this->manageParentAccountIdData($customerId);
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage(__('Something went wrong while saving parent account'));
        }
    }

    /**
     * Manage parent account id data.
     *
     * @return $this
     */
    protected function manageParentAccountIdData($entityId)
    {
        if (!$entityId) {
            return $this;
        }

        // set a flag, that this function is fired for the first time
        $this->request->setParam('manageParentAccountIdDataFired', 1);

        $parentAccountId = $this->retrieveParentAccountId();
        if (!$parentAccountId) {
            /** If there is no parent account id, then make sure, that the customer is master account. */
            $this->parentAccountConverter->convertCustomer($entityId);
        } else {
            /** If the parent account id exists, then make sure, that the customer is sub account of that parent. */
            $this->accountAssigner->assignCustomerToParent($parentAccountId, $entityId);
        }
    }

    /**
     * Get customer post data.
     *
     * @return array
     */
    protected function getCustomerPostData()
    {
        return $this->request->getParam('customer') ?: [];
    }

    /**
     * Retrieve parent account id, which was sent by post.
     *
     * @return int|null
     */
    protected function retrieveParentAccountId()
    {
        $customerData = $this->getCustomerPostData();
        if (isset($customerData['parent_account_id'])) {
            if ($this->moduleConfig->showAsText()) {
                $customerEmail = trim($customerData['parent_account_id']);
                if (!empty($customerEmail)) {
                    if ($customerData['email'] !== $customerEmail) {
                        $parentAccount = $this->parentAccountInterface->getByEmail($customerEmail);
                        if ($parentAccount && $parentAccount->getId()) {
                            return (int)$parentAccount->getId();
                        }
                    } else {
                        $this->messageManager->addErrorMessage(__('Parent email can not be the same as subaccount email.'));
                    }
                }
                return null;
            } else {
                return (int)$customerData['parent_account_id'];
            }
        }
        return null;
    }
}
