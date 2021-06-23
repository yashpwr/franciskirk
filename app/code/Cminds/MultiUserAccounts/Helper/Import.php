<?php
namespace Cminds\MultiUserAccounts\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Model\StoreManagerInterface;
/**
 * Cminds MultiUserAccounts import helper.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Oleh Petryk
 */
class Import extends AbstractHelper
{

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Object initialization.
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param Encryptor $encryptor
     * @param Context $context
     * @param AccountManagementInterface $accountManagement
     * @param CustomerInterfaceFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param DataObjectHelper $dataObjectHelper
     *
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Encryptor $encryptor,
        Context $context,
        AccountManagementInterface $accountManagement,
        CustomerInterfaceFactory $customerFactory,
        StoreManagerInterface $storeManager,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->encryptor = $encryptor;
        $this->accountManagement = $accountManagement;
        $this->customerFactory = $customerFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    public function prepareNotSavedAttributes($profileData,$savedCustomerData) {
        unset($savedCustomerData['addresses']);

        return array_diff($profileData, $savedCustomerData);
    }

    public function checkParentAccount($accountData) {
        try {
            $subaccountParent = $this->customerRepository
                ->get($accountData['parent_email']);

            return $subaccountParent;
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(
                __(
                    'Parent account "%1" does not exists.',
                    $accountData['parent_email']
                )
            );
        }
    }

    public function checkParentWithWebsite($accountData) {
        try {
            $websiteId = null;
            if (!empty($accountData['website_id'])) {
                $websiteId = $accountData['website_id'];
            }

            $subaccountParent = $this->customerRepository
                ->get($accountData['parent_email'], $websiteId);

            return $subaccountParent;
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(
                __(
                    'Parent account "%1" does not exists.',
                    $accountData['parent_email']
                )
            );
        }
    }

    public function getParentsData($preparedData) {
        if (isset($preparedData['accounts'])) {
            return $preparedData['accounts'];
        } else {
            return false;
        }
    }

    public function getSubaccountsData($preparedData) {
        if (isset($preparedData['subaccounts'])) {
            return $preparedData['subaccounts'];
        } else {
            return false;
        }
    }

    public function checkIfEmailAvailable($email, $updateFlag) {
        $isEmailAvailable = $this->accountManagement->isEmailAvailable($email);


        if (!$isEmailAvailable) {
            if (!$updateFlag) {
                throw new LocalizedException(
                    __(
                        'Account with email "%1" already exists.',
                        $email
                    )
                );
            }
        }

        return $isEmailAvailable;
    }

    public function getUpdatedCustomer($account, $customerId) {
        $customer = $this->customerRepository->getById($customerId);

        foreach ($account as $key => $value) {
            $customer->setData($key, $value);
        }

        return $customer;
    }

    public function getCustomerObject($customerProfile) {
        $customer = $this->customerFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $customer,
            $customerProfile,
            '\Magento\Customer\Api\Data\CustomerInterface'
        );

        $this->customerRepository->save($customer);

        return $this->customerRepository->get($customer->getEmail(), $customer->getWebsiteId());
    }

    /**
     * @return string|int
     */
    public function getDefaultWebsiteId()
    {
        return $this->storeManager->getDefaultStoreView()->getWebsiteId();
    }
}
