<?php

namespace Cminds\MultiUserAccounts\Model\Service\Assign;

use Cminds\MultiUserAccounts\Model\SubaccountFactory;
use Exception;

class Account
{
    /**
     * Sub Account Factory.
     *
     * @var SubaccountFactory
     */
    private $subaccountFactory;

    /**
     * Account constructor.
     *
     * @param SubaccountFactory $subaccountFactory
     */
    public function __construct(
        SubaccountFactory $subaccountFactory
    ) {
        $this->subaccountFactory = $subaccountFactory;
    }

    /**
     * Assign customer to new customer as a sub account.
     *
     * @param int $parentCustomerId
     * @param int $customerId
     *
     * @throws Exception
     */
    public function assignCustomerToParent($parentCustomerId, $customerId)
    {
        if (!$parentCustomerId || !$customerId) {
            throw new Exception(__('Some arguments are missing in assignCustomerToParent method'));
        }

        if (!is_int($parentCustomerId) || !is_int($customerId)) {
            throw new Exception(__('Some arguments are not of integer type'));
        }

        $subaccount = $this->subaccountFactory->create()
            ->load($customerId, 'customer_id');
        if (!$subaccount->getId()) {
            $subaccount = $this->subaccountFactory->create()
                ->setPermission(0);
        }

        $subaccount
            ->setParentCustomerId($parentCustomerId)
            ->setCustomerId($customerId)
            ->save();
    }
}
