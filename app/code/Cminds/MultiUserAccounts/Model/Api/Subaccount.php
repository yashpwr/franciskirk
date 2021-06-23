<?php

namespace Cminds\MultiUserAccounts\Model\Api;

use Cminds\MultiUserAccounts\Api\Data\ApiSubAccountInterface;
use Cminds\MultiUserAccounts\Api\SubaccountInterface;
use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Model\Import;
use Cminds\MultiUserAccounts\Model\Permission;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount as SubaccountResourceModel;
use Cminds\MultiUserAccounts\Model\ResourceModel\SubaccountRepository as SubaccountRepository;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Subaccount implements SubaccountInterface
{
    private $dataObjectFactory;
    private $addressRepository;
    private $customerRepository;
    private $subaccountResourceModel;
    private $subaccountRepository;
    private $subaccountTransportRepository;
    private $permission;
    private $parentaccountModel;
    private $customerRegistry;
    private $import;

    /**
     * Variable disables validation parent customer id validation when
     * subaccount is promoted.
     *
     * @var bool
     */
    private $processValidate = false;

    public function __construct(
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        SubaccountResourceModel $subaccountResourceModel,
        SubaccountRepository $subaccountRepository,
        SubaccountTransportRepositoryInterface $subaccountTransportRepository,
        Permission $permission,
        Import $import,
        CustomerRegistry $customerRegistry,
        DataObjectFactory $dataObjectFactory,
        Parentaccount $parentaccountModel
    ) {
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->subaccountResourceModel = $subaccountResourceModel;
        $this->subaccountRepository = $subaccountRepository;
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->permission = $permission;
        $this->import = $import;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->customerRegistry = $customerRegistry;
        $this->parentaccountModel = $parentaccountModel;
    }

    public function getSubaccounts($parentId)
    {
        $subaccounts = $this->fetchParentSubaccounts($parentId);
        $subaccounts = array_values($subaccounts);

        return $subaccounts;
    }

    public function fetchParentSubaccounts($parentId)
    {
        try {
            $customer = $this->getCustomerById($parentId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Customer with provided id does not exist or is not a parent account.')
            );
        }

        try {
            $subaccountIds = $this->subaccountResourceModel
                ->getSubaccountIdsByParentCustomerId($parentId);

            if (count($subaccountIds) === 0) {
                throw new NoSuchEntityException(
                    __('Parent account does not have any sub accounts.')
                );
            }
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Parent account does not have any sub accounts.')
            );
        }

        $subaccounts = [];

        foreach ($subaccountIds as $subaccountId) {

            $parentData = $this->getCustomerById($parentId);

            $customerId = $this->subaccountRepository
                ->getById($subaccountId)
                ->getCustomerId();

            $subaccount = $this->getCustomerById($customerId);

            if ($subaccountId) {
                $subaccountTransportDataObject = $this
                    ->getSubAccountTransportDataObject($subaccountId);
            }

            foreach ($subaccount->getAddresses() as $subAddress) {
                if ($subAddress->isDefaultShipping()) {
                    $subaccountAddress = $subAddress;
                }
            }

            if (!isset($subaccountAddress)) {
                $payload = [
                    "city" => "",
                    "country_id" => "",
                    "region" => "",
                    "postcode" => "",
                    "telephone" => "",
                    "street" => [],
                ];

                $subaccountAddress = $this->dataObjectFactory->create($payload);
            }

            $customer = $this->customerRepository->getById($customerId);
            $customerActive = $customer->getCustomAttribute('customer_is_active');
            $is_active = 0;
            if (isset($customerActive) && $customerActive->getValue()  !== null) {
                $is_active = $customerActive->getValue();
            }

            $streetSubArray = $this->getStreetArray($subaccountAddress);

            $subaccounts[$subaccount->getEmail()] = [
                'id' => $subaccount->getId(),
                'parent_email' => $parentData->getEmail(),
                'parent_id' => $parentId,
                'firstname' => $subaccount->getFirstname(),
                'lastname' => $subaccount->getLastname(),
                'email' => $subaccount->getEmail(),
                'website_id' => $subaccount->getWebsiteId(),
                'group_id' => $subaccount->getGroupId(),
                'prefix' => $subaccount->getPrefix(),
                'middlename' => $subaccount->getMiddlename(),
                'suffix' => $subaccount->getSuffix(),
                'dob' => $subaccount->getDob(),
                'taxvat' => $subaccount->getTaxvat(),
                'gender' => $subaccount->getGender(),
                'is_active' => $is_active,
                'company' => '',
                'city' => $subaccountAddress->getCity(),
                'country_id' => $subaccountAddress->getCountryId(),
                'region' => $subaccountAddress->getRegionId(),
                'postcode' => $subaccountAddress->getPostcode(),
                'telephone' => $subaccountAddress->getTelephone(),
                'fax' => '',
                'vat_id' => '',
                'street_1' => $streetSubArray[0],
                'street_2' => $streetSubArray[1],
                'account_data_modification_permission' =>
                    (int)$subaccountTransportDataObject->getAccountDataModificationPermission(),
                'account_order_history_view_permission' =>
                    (int)$subaccountTransportDataObject->getAccountOrderHistoryViewPermission(),
                'checkout_order_create_permission' =>
                    (int)$subaccountTransportDataObject->getCheckoutOrderCreatePermission(),
                'checkout_order_approval_permission' =>
                    (int)$subaccountTransportDataObject->getCheckoutOrderApprovalPermission(),
                'checkout_cart_view_permission' =>
                    (int)$subaccountTransportDataObject->getCheckoutCartViewPermission(),
                'manage_subaccounts' =>
                    (int)$subaccountTransportDataObject->getManageSubaccounts(),
                'checkout_view_permission' =>
                    (int)$subaccountTransportDataObject->getCheckoutViewPermission(),
                'checkout_order_placed_notification_permission' =>
                    (int)$subaccountTransportDataObject->getCheckoutOrderPlacedNotificationPermission(),
                'force_usage_parent_company_name_permission' =>
                    (int)$subaccountTransportDataObject->getForceUsageParentCompanyNamePermission(),
                'force_usage_parent_vat_permission' =>
                    (int)$subaccountTransportDataObject->getForceUsageParentVatPermission(),
                'force_usage_parent_addresses_permission' =>
                    (int)$subaccountTransportDataObject->getForceUsageParentAddressesPermission(),
            ];
        }

        return $subaccounts;
    }

    /**
     * Loads customer object from customer Id
     *
     * @param integer $id Customer ID.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function getCustomerById($id)
    {
        return $this->customerRepository->getById($id);
    }

    /**
     * Get sub-account data.
     *
     * @param int $parentId
     * @param int $subaccountId
     *
     * @return DataObject
     * @throws NoSuchEntityException
     */
    public function getById($parentId, $subaccountId)
    {
        try {
            $subaccount = $this->subaccountRepository->getByCustomerId($subaccountId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Subaccount with provided id does not exist.')
            );
        }

        if ($this->processValidate && $subaccount->getParentCustomerId() !== $parentId) {
            throw new NoSuchEntityException(
                __('Subaccount with provided id does not exist.')
            );
        }

        $this->processValidate = false;
        $result = $this->dataObjectFactory->create();

        $subaccountIds = $this->subaccountResourceModel->getSubaccountIdsByParentCustomerId($parentId);
        if (!empty($subaccountIds)) {
            $subaccounts = $this->fetchParentSubaccounts($parentId); // @TODO: Fetching single subaccount would be nice.

            foreach ($subaccounts as $subaccount) {
                if ($subaccount['id'] == $subaccountId) {
                    $result->setData($subaccount);

                    return $result;
                }
            }
        }

        throw new NoSuchEntityException(
            __('Subaccount with provided id does not exist or is not a child of provided parent id.')
        );
    }

    /**
     * Create new sub account
     *
     * @param int                    $parentId
     * @param ApiSubAccountInterface $customer
     *
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function create($parentId, ApiSubAccountInterface $customer)
    {
        try {
            $parent = $this->getCustomerById($parentId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Customer with provided parent id does not exist.')
            );
        }

        try {
            $this->import
                ->initSourceProcessor(Import::SOURCE_API)
                ->setApiData($customer)
                ->setUpdateFlag(false)
                ->setParentId($parentId)
                ->setParentEmail();
            $this->import->execute();
        } catch (\Exception $e) {
            throw new LocalizedException(
                __("Subaccount has been not created. Details: " . $e->getMessage())
            );
        }

        $customer = $this->customerRegistry->retrieveByEmail($customer->getEmail(), $customer->getWebsiteId());

        return $this->getById($parentId, $customer->getId());
    }

    /**
     * Update sub account by it's id
     *
     * @param int                    $parentId
     * @param int                    $subaccountId
     * @param ApiSubAccountInterface $customer
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateById(
        $parentId,
        $subaccountId,
        ApiSubAccountInterface $customer
    ) {
        $downgradeFlag = false;
        if ($customer->getDowngrade() !== null && $customer->getDowngrade() === true) {
            $downgradeFlag = true;
        }
        if ($customer->getParentEmail()) {
            $downgradeFlag = true;
        }
        if (!$downgradeFlag) {
            try {
                $subaccount = $this->subaccountRepository->getByCustomerId($subaccountId);
            } catch (LocalizedException $e) {
                throw new LocalizedException(
                    __('Customer with provided id is not a sub account or does not exist.')
                );
            }
            if ($subaccount->getParentCustomerId() !== $parentId) {
                throw new NoSuchEntityException(
                    __('Customer parent account does not exist or is not a parent of sub account.')
                );
            }
        }

        try {
            $parentEmail = $customer->getParentEmail();

            if ($downgradeFlag) {
                $this->import->setCustomerId($subaccountId);
                $this->import->setParentId($parentId);
                $this->import->setLinkFlag(true);
                $this->import
                    ->initSourceProcessor(Import::SOURCE_API)
                    ->setApiData($customer)
                    ->setUpdateFlag(true)
                    ->setParentId($parentId)
                    ->setParentEmail($parentEmail)
                    ->setSubId($subaccountId);
                    
                $this->import
                    ->setUpdateFlag(true)
                    ->execute();
            } else {
                $this->import->setCustomerId($subaccountId);
                $this->import->setParentId($parentId);
                $this->import
                    ->initSourceProcessor(Import::SOURCE_API)
                    ->setApiData($customer)
                    ->setUpdateFlag(true)
                    ->setParentId($parentId)
                    ->setParentEmail($parentEmail)
                    ->setSubId($subaccountId);
                    
                $this->import
                    ->setUpdateFlag(true)
                    ->execute();
            }
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Sub account has not been updated. Details: '.$e->getMessage() )
            );
        }
        if ($this->import->getWasLinked()) {
            $newParent = $this->parentaccountModel->getById($parentId);

            return $newParent;
        }
        if ($this->import->getWasPromoted()) {
            $this->processValidate = true;

            return $this->getSubaccounts($subaccountId);
        } else {
            $this->processValidate = true;

            return $this->getById($parentId, $subaccountId);
        }
    }

    /**
     * Delete sub-account by id.
     *
     * @param int $parentId
     * @param int $subaccountId
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function deleteById($parentId, $subaccountId)
    {
        $result = [];
        $subIdsArray = $this->subaccountResourceModel->getSubaccountIdsByParentCustomerId($parentId);

        if (!empty($subIdsArray)) {
            try {
                $subaccount = $this->subaccountTransportRepository->getByCustomerId($subaccountId);
            } catch (NoSuchEntityException $e) {
                throw new NoSuchEntityException(
                    __('Subaccount with provided id does not exist.')
                );
            }
            $id = $subaccount->getCustomerId();

            if (in_array($id, $subIdsArray)) {
                $this->subaccountTransportRepository->deleteById($id);
            } else {
                throw new NoSuchEntityException(
                    __('Parent account with provided id does not have sub accounts.')
                );
            }
        } else {
            throw new NoSuchEntityException(
                __('Customer with provided id is not parent account or has no related subaccounts.')
            );
        }

        return 1;
    }

    /**
     * Private method ensures that street value is an array.
     *
     * @param $address
     *
     * @return array
     */
    private function getStreetArray($address)
    {
        $streetArray = [];

        if ($address->getStreet() !== null) {
            foreach ($address->getStreet() as $street) {
                $streetArray[] = $street;
            }
        }

        if (count($streetArray) == 0) {
            $streetArray[] = '';
        }

        if (count($streetArray) == 1) {
            $streetArray[] = '';
        }

        return $streetArray;
    }

    /**
     * Loads Sub Account Transport Data Object
     *
     * @param $sub
     *
     * @return \Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface
     */
    private function getSubAccountTransportDataObject($sub)
    {
        $existingSubaccountTransportDataObject = $this
            ->subaccountTransportRepository
            ->getById($sub);
        $this->permission->loadSubaccountPermissions(
            $existingSubaccountTransportDataObject
        );

        return $existingSubaccountTransportDataObject;
    }
}
