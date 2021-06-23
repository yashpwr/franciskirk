<?php

namespace Cminds\MultiUserAccounts\Observer\Customer;

use Cminds\MultiUserAccounts\Api\Data\SubaccountInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

/**
 * Cminds MultiUserAccounts before customer save observer.
 * Will be executed on "customer_save_before" event.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class SaveBefore implements ObserverInterface
{
    const SKIP_PERMISSION_CHECK = 'cminds_multiuseraccounts_skip_permission_check';

    /**
     * Customer session object.
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Module config object.
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Object initialization.
     *
     * @param CustomerSession $customerSession
     * @param ModuleConfig $moduleConfig
     * @param ViewHelper $viewHelper
     * @param Registry $coreRegistry
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        CustomerSession $customerSession,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        Registry $coreRegistry,
        CustomerFactory $customerFactory
    ) {
        $this->customerSession = $customerSession;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->coreRegistry = $coreRegistry;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Check permission in before save event handler.
     *
     * @param Observer $observer Observer object.
     *
     * @return SaveBefore
     * @throws \RuntimeException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        //@TODO check for nested
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            return $this;
        }

        if ($this->coreRegistry->registry(self::SKIP_PERMISSION_CHECK)) {
            return $this;
        }

        /** @var SubaccountInterface $subaccount */
        $subaccount = $this->customerSession->getSubaccountData();

        $accountDataModificationPermission = (bool)$subaccount
            ->getAccountDataModificationPermission();
        if ($accountDataModificationPermission === false) {
            throw new LocalizedException(
                __('You don\'t have permission to edit account information.')
            );
        }

        $forceVat = (bool)$subaccount
            ->getForceUsageParentVatPermission();
        if ($forceVat === true) {
            $customer = $observer->getCustomer();

            $this->coreRegistry->register(LoadAfter::LOAD_EVENT_SKIP, true);
            $orgCustomer = $this->customerFactory->create()
                ->load($customer->getId());
            $this->coreRegistry->unregister(LoadAfter::LOAD_EVENT_SKIP);

            $customer->setTaxvat($orgCustomer->getTaxvat());
        }

        return $this;
    }
}
