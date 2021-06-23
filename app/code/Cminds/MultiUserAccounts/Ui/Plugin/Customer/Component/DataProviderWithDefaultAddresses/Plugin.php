<?php

namespace Cminds\MultiUserAccounts\Ui\Plugin\Customer\Component\DataProviderWithDefaultAddresses;

use Cminds\MultiUserAccounts\Api\SubaccountRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Cminds MultiUserAccounts data provider plugin
 *
 * @category    Cminds
 * @package     Cminds_MultiUserAccounts
 * @author      Piotr Pierzak <piotr@cminds.com>
 */
class Plugin
{
    /**
     * Subaccount repository object.
     *
     * @var SubaccountRepositoryInterface
     */
    private $subaccountRepository;

    /**
     * Customer repository object.
     *
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Object initialization.
     *
     * @param SubaccountRepositoryInterface $subaccountRepository Subaccount
     *     repository object.
     * @param CustomerRepositoryInterface   $customerRepository Customer
     *     repository object.
     */
    public function __construct(
        SubaccountRepositoryInterface $subaccountRepository,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->subaccountRepository = $subaccountRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Add subaccount parent_customer_id id to data array.
     *
     * @param \Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses $subject Subject object.
     * @param array        $data Data array.
     *
     * @return array
     * @throws LocalizedException
     */
    public function afterGetData(
        \Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses $subject,
        $data
    ) {
        if (!isset($data['items'])) {
            return $data;
        }

        foreach ($data['items'] as &$item) {
            try {
                $subaccountModel = $this->subaccountRepository
                    ->getByCustomerId($item['entity_id']);

                $customerModel = $this->customerRepository
                    ->getById($subaccountModel->getParentCustomerId());

                $item['parent_customer_id'] = $subaccountModel
                    ->getParentCustomerId();
                $item['parent_customer_firstname'] = $customerModel
                    ->getFirstname();
                $item['parent_customer_lastname'] = $customerModel
                    ->getLastname();
                $item['parent_customer_middlename'] = $customerModel
                    ->getMiddlename();
                $item['parent_customer_suffix'] = $customerModel
                    ->getSuffix();
                $item['parent_customer_prefix'] = $customerModel
                    ->getPrefix();
            } catch (NoSuchEntityException $e) {
                $item['parent_customer_id'] = null;
                $item['parent_customer_prefix'] = null;
                $item['parent_customer_firstname'] = null;
                $item['parent_customer_middlename'] = null;
                $item['parent_customer_lastname'] = null;
                $item['parent_customer_suffix'] = null;
            }
        }

        return $data;
    }
}