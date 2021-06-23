<?php

namespace Cminds\MultiUserAccounts\Model\Plugin\Customer\Customer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Registry;
use Cminds\MultiUserAccounts\Model\Import;
use Cminds\MultiUserAccounts\Model\SubaccountFactory;
use Magento\Customer\Api\Data\CustomerExtensionFactory;

/**
 * Cminds MultiUserAccounts customer model plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Plugin
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * Customer Extension Factory.
     *
     * @var CustomerExtensionFactory
     */
    private $customerExtensionFactory;

    /**
     * Subaccount Factory.
     *
     * @var SubaccountFactory
     */
    private $subaccountFactory;

    /**
     * Plugin constructor.
     *
     * @param Registry $coreRegistry
     * @param CustomerExtensionFactory $customerExtensionFactory
     * @param SubaccountFactory $subaccountFactory
     */
    public function __construct(
        Registry $coreRegistry,
        CustomerExtensionFactory $customerExtensionFactory,
        SubaccountFactory $subaccountFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->subaccountFactory = $subaccountFactory;
    }

    /**
     * @param Customer $subject
     * @param \Closure $proceed
     * @param string   $type
     * @param string   $backUrl
     * @param string   $storeId
     *
     * @return Customer|mixed
     */
    public function aroundSendNewAccountEmail(
        Customer $subject,
        \Closure $proceed,
        $type = 'registered',
        $backUrl = '',
        $storeId = '0'
    ) {
        if ($this->coreRegistry->registry(Import::SKIP_CUSTOMER_WELCOME_EMAIL)) {
            return $subject;
        }

        $proceed($type, $backUrl, $storeId);

        return $subject;
    }

    /**
     * Modify REST API response.
     *
     * @param Customer $subject
     * @param CustomerInterface $customerInterface
     *
     * @return CustomerInterface
     * @todo check if module is enabled or not via Cminds/MultiUserAccounts/Model/Config
     */
    public function afterGetDataModel(Customer $subject, CustomerInterface $customerInterface)
    {
        $id = $customerInterface->getId();
        if (!$id) {
            return $customerInterface;
        }

        $extensionAttributes = $customerInterface->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->customerExtensionFactory->create();
        }

        $subaccountModel = $this->subaccountFactory->create()
            ->load($id, 'customer_id');
        if ($subaccountModel->getId()) {
            /** Set parent id to the response */
            $extensionAttributes->setParentId($subaccountModel->getParentCustomerId());
        }

        /** Set subaccounts ids to the response */
        $subaccounts = $this->subaccountFactory->create()
            ->getCollection()
            ->addFieldToFilter('parent_customer_id', $id)
            ->load();

        if ($subaccounts->getSize()) {
            $subaccountIds = [];

            foreach ($subaccounts as $subaccount) {
                $subaccountIds[] = $subaccount->getCustomerId();
            }

            $subaccountIds = implode(",", $subaccountIds);
            $extensionAttributes->setSubaccounts($subaccountIds);
        }

        $customerInterface->setExtensionAttributes($extensionAttributes);

        return $customerInterface;
    }
}
