<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Helper\Plugin\Customer\Session\CurrentCustomerAddress;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\AccountManagement;
use Magento\Customer\Helper\Session\CurrentCustomerAddress;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Cminds MultiUserAccounts customer address helper plugin.
 */
class Plugin
{
    const PLUGIN_SKIP = 'cminds_multiuseraccounts_customer_address_collection_plugin_skip';

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @var AccountManagement
     */
    private $accountManagement;

    /**
     * Object constructor.
     *
     * @param CustomerSession            $customerSession
     * @param ModuleConfig               $moduleConfig
     * @param ViewHelper                 $viewHelper
     * @param AccountManagement          $accountManagement
     */
    public function __construct(
        CustomerSession $customerSession,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        AccountManagement $accountManagement
    ) {
        $this->customerSession = $customerSession;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->accountManagement = $accountManagement;
    }

    /**
     * @param CurrentCustomerAddress $subject
     * @param \Closure $closure
     * @return \Magento\Customer\Api\Data\AddressInterface|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetDefaultBillingAddress(CurrentCustomerAddress $subject, \Closure $closure)
    {
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            return $closure();
        }

        $storeId = $this->viewHelper->getStoreId();
        $customerId = $this->customerSession->getCustomer()->getId();

        /** @var SubaccountTransportInterface $subaccountTransportDataObject */
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();
        $canManageAddresses = (bool)($subaccountTransportDataObject
            ->getAccountAddressBookModificationPermission());
        $forceAddresses = (bool)$subaccountTransportDataObject
            ->getForceUsageParentAddressesPermission();

        if ($forceAddresses === false && $canManageAddresses === true) {
            return $closure();
        }

        if ($forceAddresses === true) {
            return $this->accountManagement
                ->getDefaultBillingAddress($subaccountTransportDataObject->getParentCustomerId());
        }

        if ($canManageAddresses === false) {
            $addresses = $this->customerSession->getCustomer()->getAddresses();
            foreach ($addresses as $address) {
                if ($address->getStoreView() === $storeId) {
                    return $this->accountManagement
                        ->getBillingAddressById($customerId, $address->getId());
                }
            }
        }

        return $closure();
    }

    /**
     * @param CurrentCustomerAddress $subject
     * @param \Closure $closure
     * @return \Magento\Customer\Api\Data\AddressInterface|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetDefaultShippingAddress(CurrentCustomerAddress $subject, \Closure $closure)
    {
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            return $closure();
        }

        $storeId = $this->viewHelper->getStoreId();
        $customerId = $this->customerSession->getCustomer()->getId();

        /** @var SubaccountTransportInterface $subaccountTransportDataObject */
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();
        $canManageAddresses = (bool)($subaccountTransportDataObject
            ->getAccountAddressBookModificationPermission());
        $forceAddresses = (bool)$subaccountTransportDataObject
            ->getForceUsageParentAddressesPermission();

        if ($forceAddresses === false && $canManageAddresses === true) {
            return $closure();
        }

        if ($forceAddresses === true) {
            return $this->accountManagement
                ->getDefaultShippingAddress($subaccountTransportDataObject->getParentCustomerId());
        }

        if ($canManageAddresses === false) {
            $addresses = $this->customerSession->getCustomer()->getAddresses();
            foreach ($addresses as $address) {
                if ($address->getStoreView() === $storeId) {
                    return $this->accountManagement
                        ->getBillingAddressById($customerId, $address->getId());
                }
            }
        }

        return $closure();
    }
}
