<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model\Plugin\Customer\Address\Collection\Load;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;

/**
 * Cminds MultiUserAccounts customer address collection plugin.
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
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * Object constructor.
     *
     * @param CustomerSession $customerSession
     * @param ModuleConfig    $moduleConfig
     * @param ViewHelper      $viewHelper
     * @param CustomerFactory $customerFactory
     * @param Registry $coreRegistry
     */
    public function __construct(
        CustomerSession $customerSession,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        CustomerFactory $customerFactory,
        Registry $coreRegistry
    ) {
        $this->customerSession = $customerSession;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->customerFactory = $customerFactory;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Customer collection after load plugin.
     *
     * @param Collection $subject
     * @param Collection $result
     *
     * @return $this|Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterLoad(Collection $subject, Collection $result)
    {
        if ($this->coreRegistry->registry(self::PLUGIN_SKIP)) {
            return $result;
        }

        $this->coreRegistry->register(self::PLUGIN_SKIP, true);

        //@TODO check for nested
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            $this->coreRegistry->unregister(self::PLUGIN_SKIP);

            return $result;
        }

        /** @var SubaccountTransportInterface $subaccountTransportDataObject */
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();

        $forceCompanyName = (bool)$subaccountTransportDataObject
            ->getForceUsageParentCompanyNamePermission();
        $forceVat = (bool)$subaccountTransportDataObject
            ->getForceUsageParentVatPermission();
        $forceAddress = (bool)$subaccountTransportDataObject
            ->getForceUsageParentAddressesPermission();
        $canManageAddresses = (bool)($subaccountTransportDataObject
            ->getAccountAddressBookModificationPermission());

        if ($forceCompanyName === false
            && $forceVat === false
            && $forceAddress === false
            && $canManageAddresses === true
        ) {
            $this->coreRegistry->unregister(self::PLUGIN_SKIP);

            return $this;
        }

        /** @var Customer $parentCustomer */
        $parentCustomer = $this->customerFactory->create()
            ->load($subaccountTransportDataObject->getParentCustomerId());

        /** @var Address $parentBillingAddress */
        $parentBillingAddress = $parentCustomer->getDefaultBillingAddress();

        if ($parentBillingAddress !== false) {
            // If forced address is enable make it only one available.
            if ($forceAddress) {
                $result->removeAllItems();
                //add the parent address item
                $result->addItem($parentBillingAddress);
            }

            // Set in all available addresses forced data if option enabled.
            if ($result->getItems()) {
                foreach ($result->getItems() as $address) {
                    if ($forceCompanyName) {
                        $address->setCompany($parentBillingAddress->getCompany());
                    }
                    if ($forceVat) {
                        $address->setVatId($parentBillingAddress->getVatId());
                    }
                }
            }
        }

        if ($canManageAddresses === false) {
            $storeId = $this->viewHelper->getStoreId();
            $addresses = $this->customerSession->getCustomer()->getAddresses();
            foreach ($addresses as $address) {
                if ($address->getStoreView() === $storeId) {
                    $result->removeAllItems();
                    //add the store view address item
                    $result->addItem($address);
                }
            }
        }

        $this->coreRegistry->unregister(self::PLUGIN_SKIP);

        return $result;
    }
}
