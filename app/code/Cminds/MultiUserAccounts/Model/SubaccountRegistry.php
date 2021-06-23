<?php
/**
 * Cminds MultiUserAccounts Subaccount Registry Model.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class SubaccountRegistry
 * @package Cminds\MultiUserAccounts\Model
 */
class SubaccountRegistry
{
    const REGISTRY_SEPARATOR = ':';

    /**
     * Subaccounts array.
     *
     * @var Subaccount[]
     */
    private $subaccountRegistryById = [];

    /**
     * Subaccounts array.
     *
     * @var Subaccount[]
     */
    private $subaccountRegistryByCustomerId = [];

    /**
     * Subaccount factory object.
     *
     * @var SubaccountFactory
     */
    private $subaccountFactory;

    /**
     * Object initialization.
     *
     * @param SubaccountFactory $subaccountFactory Subaccount factory object.
     */
    public function __construct(
        SubaccountFactory $subaccountFactory
    ) {
        $this->subaccountFactory = $subaccountFactory;
    }

    /**
     * Get instance of the Subaccount model identified by id.
     *
     * @param int $subaccountId Subaccount id.
     *
     * @return Subaccount
     * @throws NoSuchEntityException
     */
    public function retrieve($subaccountId)
    {
        if (isset($this->subaccountRegistryById[$subaccountId])) {
            return $this->subaccountRegistryById[$subaccountId];
        }

        $subaccount = $this->subaccountFactory->create()
            ->load($subaccountId);
        if (!$subaccount->getId()) {
            $subaccount->loadByCustomerId($subaccountId);
        }
        
        if (!$subaccount->getId()) {
            throw NoSuchEntityException::singleField(
                'subaccountId',
                $subaccountId
            );
        }

        $this->subaccountRegistryById[$subaccountId] = $subaccount;

        return $subaccount;
    }

    /**
     * Retrieve subaccount model from registry by customer id.
     *
     * @param int $customerId Customer id.
     * @param bool $skipCache - skip subaccountRegistryByCustomerId check
     *
     * @return Subaccount
     * @throws NoSuchEntityException
     */
    public function retrieveByCustomerId($customerId, $skipCache = false)
    {
        $customerIdKey = $this->getCustomerIdKey($customerId);
        if ($skipCache === false && isset($this->subaccountRegistryByCustomerId[$customerIdKey])
        ) {
            return $this->subaccountRegistryByCustomerId[$customerIdKey];
        }

        /** @var Subaccount $subaccount */
        $subaccount = $this->subaccountFactory->create();

        $subaccount->loadByCustomerId($customerId);
        if (!$subaccount->getId()) {
            throw new NoSuchEntityException(
                __(
                    NoSuchEntityException::MESSAGE_SINGLE_FIELD,
                    [
                        'fieldName' => 'customerId',
                        'fieldValue' => $customerId,
                    ]
                )
            );
        }

        $this->subaccountRegistryById[$subaccount->getId()] = $subaccount;
        $this->subaccountRegistryByCustomerId[$customerId] = $subaccount;

        return $subaccount;
    }

    /**
     * Retrieve subaccount customer id registry key.
     *
     * @param int $customerId Customer id.
     *
     * @return string
     */
    private function getCustomerIdKey($customerId)
    {
        return $customerId;
    }

    /**
     * Remove an instance of the Subaccount model from the registry.
     *
     * @param int $subaccountId Customer id.
     *
     * @return SubaccountRegistry
     */
    public function remove($subaccountId)
    {
        unset($this->subaccountRegistryById[$subaccountId]);

        return $this;
    }

    /**
     * Replace existing Subaccount model with a new one.
     *
     * @param Subaccount $subaccount Subaccount object.
     *
     * @return SubaccountRegistry
     */
    public function push(Subaccount $subaccount)
    {
        $this->subaccountRegistryById[$subaccount->getId()] = $subaccount;

        $customerId = $subaccount->getCustomerId();
        $this->subaccountRegistryByCustomerId[$customerId] = $subaccount;

        return $this;
    }
}
