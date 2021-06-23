<?php

namespace Cminds\MultiUserAccounts\Model\Import;

use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Cminds\MultiUserAccounts\Model\Import\Validator;
use Cminds\MultiUserAccounts\Helper\Import as ImportHelper;
use Cminds\MultiUserAccounts\Model\Import\DataResolver;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Reflection\DataObjectProcessor;
use Cminds\MultiUserAccounts\Api\Data\SubaccountInterfaceFactory;
use Cminds\MultiUserAccounts\Model\Permission;
use Cminds\MultiUserAccounts\Api\SubaccountRepositoryInterface;
use Cminds\MultiUserAccounts\Model\Import;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Customer\Model\CustomerFactory as CustomerModelFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Customer\Model\CustomerRegistry;

class Process
{
    /**
     * @var CustomerModelFactory
     */
    private $customerModelFactory;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SubaccountRepositoryInterface
     */
    private $subaccountRepository;

    /**
     * @var SubaccountInterfaceFactory
     */
    private $subaccountFactory;
    /**
     * @var DataObjectProcessor
     */
    private $dataProcessor;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
     /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ImportHelper
     */
    private $importHelper;

    /**
     * @var DataResolver
     */
    private $dataResolver;

    /**
     * @var Permission
     */
    private $permission;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    private $addressRepository;

     /**
     * @var SubaccountCollectionFactory
     */
    private $subaccountCollectionFactory;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var bool
     */
    public $linkFlag = false;

    /**
     * @var bool
     */
    public $wasPromotedFlag = false;

    /**
     * @var bool
     */
    public $wasLinkedFlag = false;

    private $environment;

    private $connection;

    /**
     * @param Registry $coreRegistry
     * @param ResourceConnection $resourceConnection
     * @param Validator $validator
     * @param ImportHelper $importHelper
     * @param DataResolver $dataResolver
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param DataObjectProcessor $dataProcessor
     * @param SubaccountInterfaceFactory $subaccountFactory
     * @param SubaccountRepositoryInterface $subaccountRepository
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     * @param CountryFactory $countryFactory
     * @param CustomerModelFactory $customerModelFactory
     * @param EncryptorInterface $encryptor
     * @param CustomerRegistry $customerRegistry
     * @param Permission $permission
     *
    */
    public function __construct(
        Registry $coreRegistry,
        ResourceConnection $resourceConnection,
        Validator $validator,
        ImportHelper $importHelper,
        DataResolver $dataResolver,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        DataObjectProcessor $dataProcessor,
        SubaccountInterfaceFactory $subaccountFactory,
        SubaccountRepositoryInterface $subaccountRepository,
        SubaccountCollectionFactory $subaccountCollectionFactory,
        CountryFactory $countryFactory,
        CustomerModelFactory $customerModelFactory,
        EncryptorInterface $encryptor,
        CustomerRegistry $customerRegistry,
        Permission $permission
    )
    {
        $this->coreRegistry = $coreRegistry;
        $this->resourceConnection = $resourceConnection;
        $this->validator = $validator;
        $this->importHelper = $importHelper;
        $this->dataResolver = $dataResolver;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->dataProcessor = $dataProcessor;
        $this->subaccountFactory = $subaccountFactory;
        $this->subaccountRepository = $subaccountRepository;
        $this->subaccountCollectionFactory = $subaccountCollectionFactory;
        $this->countryFactory =$countryFactory;
        $this->customerModelFactory = $customerModelFactory;
        $this->encryptor = $encryptor;
        $this->customerRegistry = $customerRegistry;
        $this->permission = $permission;

        $this->connection = $this->resourceConnection->getConnection();
    }

    public function processImport(array $accounts, $type) {
        $firstAccount = reset($accounts);

        if ($type == 'subaccount' && isset($firstAccount['import_mode'])) {
            return $this;
        }

        try {
            $this->connection->beginTransaction();

            $accountsCount = count($accounts);
            $accountsCounter = 1;

            foreach ($accounts as $account) {

                if ($type == 'parent') {
                    $message = '[%s/%s] Processing account "%s"...';
                } else {
                    $message = '> [%s/%s] Processing subaccount "%s"...';
                }

                $this->debugLog(sprintf(
                    $message,
                    $accountsCounter,
                    $accountsCount,
                    $account['email']
                ));

                ++$accountsCounter;

                $this->processAccountData($account);
            }

            $this->connection->commit();
            $this->coreRegistry->unregister(Import::SKIP_CUSTOMER_WELCOME_EMAIL);
        } catch(LocalizedException $e) {
            $this->connection->rollBack();
            $this->coreRegistry->unregister(Import::SKIP_CUSTOMER_WELCOME_EMAIL);

            throw $e;
        }
    }

    protected function processAccountData(array $account) {
        $promoteFlag = false;
        $flag = $this->updateFlag;
        $customerId = $this->customerId;

        if (isset($account['promote']) && $account['promote'] == 1) {
            $promoteFlag = true;
            $account['parent_email'] = '';
        }

        $account = $this->validator->filterData($account);
        $customerData['profile'] = $this->dataResolver->prepareCustomerProfileData($account);

        $this->importHelper->checkIfEmailAvailable($customerData['profile']['email'], $flag);


        if ($flag) {
            $customer = $this->importHelper->getUpdatedCustomer($account, $customerId);
        } else {
            $customer = $this->importHelper->getCustomerObject($customerData['profile']);

        }

        $defaultShipping = $customer->getDefaultShipping();


        $customerData['address'] = $this->dataResolver->prepareCustomerAddressData($account);

        if (!empty($customerData['address'])) {
            $customerData['address']['region_id'] = $this->getRegionId($customerData['address']);

            if ($customerData['address']['region_id'] == null) {
                if (isset($account['region'])) {
                    $customerData['address']['region'] = $account['region'];
                }
            }

            $address = $customerData['address'];
            $region = $this->dataResolver->getRegionDataObject($address);
            unset($customerData['address']['region']);
            $addresses = $this->dataResolver->getAddressesObject($customerData);
            $addresses
                ->setRegion($region)
                ->setIsDefaultBilling(true)
                ->setIsDefaultShipping(true)
                ->setCustomerId($customer->getId());

            $this->addressRepository->save($addresses);
            $customer->setAddresses([$addresses]);
        }

        // if password attribute was sent, we need to process it separetely
        if (
            false === empty($account['password'])
            && $customer->getId()
        ) {
            $account['password_hash'] = $this->encryptor->getHash($account['password'], true);
            $customerSecure = $this->customerRegistry->retrieveSecureData( $customer->getId() );
            $customerSecure->setPasswordHash($account['password_hash']);
        }

        if (!$flag) {
            $this->customerRepository->save($customer);
        }

        /**
         * @var array $savedCustomerData
        */
        $savedCustomerData = $this->dataProcessor
            ->buildOutputDataArray(
                $customer,
                '\Magento\Customer\Api\Data\CustomerInterface'
            );

        if (isset($savedCustomerData['custom_attributes'])) {
            foreach ($savedCustomerData['custom_attributes'] as $customAttribute) {
                $savedCustomerData[$customAttribute['attribute_code']] = $customAttribute['value'];
            }

            unset($savedCustomerData['custom_attributes']);
        }

        if (!$flag) {
            $notSavedCustomerData = $this->importHelper
                ->prepareNotSavedAttributes($customerData['profile'], $savedCustomerData);

            if (!empty($notSavedCustomerData)) {
                $customerModel = $this->customerModelFactory->create()->load($customer->getId());

                foreach ($notSavedCustomerData as $key => $value) {
                    $customerModel->setData($key, $value);
                }

                $customerModel->save();
            }

            if ($account['is_active'] !== null) {
                $customer->setCustomAttribute('customer_is_active', $account['is_active']);
            }

        } else {

            // update flag true -->
            if ($account['is_active'] !== null) {
                $customer->setCustomAttribute('customer_is_active', $account['is_active']);
            }
            if (isset($account['parent_email']) && $account['parent_email'] == "") {
                $customer->setCustomAttribute('can_manage_subaccounts', 1);
                // TODO need to swap old parent from MutliUserAccounts db table
            }
            if (isset($account['can_manage_subaccounts'])) {
                if ($account['can_manage_subaccounts'] !== null) {
                    $customer->setCustomAttribute('can_manage_subaccounts', $account['can_manage_subaccounts']);
                    $this->customerRepository->save($customer);
                }
            }

            $this->customerRepository->save($customer);
        }

        if (!empty($account['parent_email'])) {
            if (!$flag) {
                $subaccountParent = $this->dataResolver->getSubaccountParent($account);
                $subaccountData = $this->dataResolver->prepareSubaccountData($account);
                $subaccount = $this->subaccountFactory->create();
                $permissionKeys = $this->permission->getPermissionKeys();

                foreach ($permissionKeys as $permissionKey) {
                    if (!empty($account[$permissionKey])) {
                        $setter = $this->permission->getPermissionSetter($permissionKey);
                        $subaccount->{$setter}($account[$permissionKey]);
                    }
                }

                $this->permission->recalculatePermission($subaccount);

                $subaccount
                    ->setCustomerId($customer->getId())
                    ->setParentCustomerId($subaccountParent->getId())
                    ->setIsActive($subaccountData['is_active']);

                $this->subaccountRepository->save($subaccount);
            } else {
                // update flag true --> parent link
                if (isset($account['parent_email']) && $this->linkFlag) {
                    $subaccount = $this->subaccountFactory->create();
                    $subaccountData = $this->dataResolver->prepareSubaccountData($account);
                    $subaccountCollection = $this->subaccountCollectionFactory->create();
                    $subaccountCollection->filterByCustomerId($this->customerId);

                    if (count($subaccountCollection) == 0) {
                        $subaccount
                            ->setCustomerId($this->customerId)
                            ->setParentCustomerId($this->parentId)
                            ->setIsActive(1);
                        $this->subaccountRepository->save($subaccount);
                    } else {
                        foreach ($subaccountCollection as $sub) {
                            if ($sub->getCustomerId() == $this->customerId) {
                                $sub->setParentCustomerId($this->parentId);
                                $sub->setIsActive(1);
                                $sub->save();
                            }
                        }
                    }
                    $this->wasLinkedFlag = true;
                } else {
                    $subaccountData = $this->dataResolver->prepareSubaccountData($account);
                    $subaccount = $this->subaccountRepository->getByCustomerId($this->customerId);
                    $permissionKeys = $this->permission->getPermissionKeys();

                    foreach ($permissionKeys as $permissionKey) {
                        if (array_key_exists($permissionKey, $account)) {
                            if ($account[$permissionKey] == 0 || $account[$permissionKey] == 1) {
                                $setter = $this->permission->getPermissionSetter($permissionKey);
                                $subaccount->{$setter}($account[$permissionKey]);
                            }
                        }
                    }

                    $this->permission->recalculatePermission($subaccount);
                    $subaccount
                        ->setCustomerId($this->customerId)
                        ->setParentCustomerId($this->parentId)
                        ->setIsActive($subaccountData['is_active']);

                    $customer->setDefaultBilling($defaultShipping)->setDefaultShipping($defaultShipping);

                    $this->customerRepository->save($customer);
                    $this->subaccountRepository->save($subaccount);
                }
            }
        } else {
            if ( true === $promoteFlag ) {
                // @todo: promote to parent
                $subaccount = $this->subaccountRepository->getByCustomerId($this->customerId);
                $currentParentId = $subaccount->getParentCustomerId();
                $subaccountCollection = $this->subaccountCollectionFactory->create();
                $subaccountCollection->filterByParentCustomerId($currentParentId);

                foreach ($subaccountCollection as $siblingSubAccount) {
                    if ((int)$siblingSubAccount->getCustomerId() === $this->customerId) {
                        $siblingSubAccount->delete();
                    }
                    $siblingSubAccount->setParentCustomerId($this->customerId);
                    $siblingSubAccount->save();
                }

                // turnes previous master into subacount
                $subaccountFromMaster = $this->subaccountFactory->create();
                $subaccountFromMaster
                    ->setCustomerId($currentParentId)
                    ->setParentCustomerId($this->customerId)
                    ->setIsActive(1);

                $this->subaccountRepository->save($subaccountFromMaster);
                $this->wasPromotedFlag = true;
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function getWasPromoted()
    {
        return $this->wasPromotedFlag;
    }

    /**
     * @return bool
     */
    public function getWasLinked()
    {
        return $this->wasLinkedFlag;
    }

    /**
     * @param string $log
     *
     * @return Import
     */
    private function debugLog($log)
    {
        if ($this->isCliEnvironment()) {
            $this->output->writeln($log);
        }

        return $this;
    }

    /**
     * @param OutputInterface $output
     *
     * @return Import
     */
    public function setOutputStream( $output)
    // public function setOutputStream(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

      /**
     * Return bool value depends if import is running in cli.
     *
     * @return bool
     */
    private function isCliEnvironment()
    {
        return $this->environment === Import::ENVIRONMENT_CLI;
    }

    /**
     * @param string $environment
     *
     * @return Import
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @param int $customerid
     *
     * @return Import
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }


    /**
     * @param string $flag
     *
     * @return Import
     */
    public function setUpdateFlag($flag)
    {
        $this->updateFlag = $flag;

        return $this;
    }

    /**
     * @param string $flag
     *
     * @return Import
     */
    public function setLinkFlag($flag)
    {
        $this->linkFlag = $flag;

        return $this;
    }

     /**
     * Set parent Id for customer that will be edited.
     *
     * @return int
     */
    public function setParentId($id)
    {
        $this->parentId = $id;
        return $id;
    }

    /**
     * Retrieve region id.
     *
     * @param array $address Address data array.
     *
     * @return int
     */
    private function getRegionId($address)
    {
        $country = $this->countryFactory->create()
            ->loadByCode($address['country_id']);

        return $country->getRegionCollection()
            ->addFieldToFilter(
                'name',
                $address['region']
            )
            ->getFirstItem()
            ->getId();
    }
}
