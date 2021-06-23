<?php

namespace Cminds\MultiUserAccounts\Model\Service\Convert\Customer;

use Cminds\MultiUserAccounts\Model\Subaccount;
use Cminds\MultiUserAccounts\Model\SubaccountFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\ResourceModel\CustomerFactory as CustomerResourceFactory;
use Cminds\MultiUserAccounts\Helper\Manage;
use Magento\Framework\Exception\LocalizedException;

class ParentAccount
{
    const CAN_MANAGE_SUBACCOUNTS = 'can_manage_subaccounts';

    /**
     * Customer Factory.
     *
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Customer Resource.
     *
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * Customer Resource Factory.
     *
     * @var CustomerResourceFactory
     */
    private $customerResourceFactory;

    /**
     * Sub Account Factory.
     *
     * @var SubaccountFactory
     */
    private $subAccountFactory;

    /**
     * Manage Helper.
     *
     * @var Manage
     */
    private $manageHelper;

    /**
     * ParentAccount constructor.
     *
     * @param CustomerFactory $customerFactory
     * @param SubaccountFactory $subaccountFactory
     * @param CustomerResource $customerResource
     * @param CustomerResourceFactory $customerResourceFactory
     * @param Manage $manageHelper
     */
    public function __construct(
        CustomerFactory $customerFactory,
        SubaccountFactory $subaccountFactory,
        CustomerResource $customerResource,
        CustomerResourceFactory $customerResourceFactory,
        Manage $manageHelper
    ) {
        $this->customerFactory = $customerFactory;
        $this->subAccountFactory = $subaccountFactory;
        $this->customerResource = $customerResource;
        $this->customerResourceFactory = $customerResourceFactory;
        $this->manageHelper = $manageHelper;
    }

    /**
     * Convert customer to the parent account.
     *
     * @param int $customerId
     *
     * @return ParentAccount
     * @throws LocalizedException
     */
    public function convertCustomer($customerId)
    {
        if (!$customerId || !is_int($customerId)) {
            throw new LocalizedException(
                __('Argument either is not specified in the convertCustomer or is not of integer tyle')
            );
        }

        $subAccount = $this->subAccountFactory->create()
            ->load($customerId, 'customer_id');
        if (!$subAccount) {
            return $this;
        }

        $this->convert($subAccount);
    }

    /**
     * The process of converting the customer to the parent account.
     *
     * @param Subaccount $subaccount
     *
     * @return ParentAccount
     */
    private function convert(Subaccount $subaccount)
    {
        $customerId = $subaccount->getCustomerId();
        if (!$customerId) {
            return $this;
        }

        $subaccount->delete();

        $value = $this->manageHelper->getCanConvertedParentsManageSubAccountsValue($customerId);

        $customer = $this->customerFactory->create()
            ->load($customerId);

        $customerData = $customer
            ->getDataModel()
            ->setId($customerId)
            ->setCustomAttribute(self::CAN_MANAGE_SUBACCOUNTS, $value);

        $customer->updateData($customerData);

        $this->customerResource = $this->customerResourceFactory->create()
            ->saveAttribute($customer, self::CAN_MANAGE_SUBACCOUNTS);
    }
}
