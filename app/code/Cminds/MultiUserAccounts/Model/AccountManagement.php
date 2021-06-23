<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model;

use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\ValidationResultsInterfaceFactory;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Config\Share as ConfigShare;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Metadata\Validator;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\Random;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils as StringHelper;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as PsrLogger;

/**
 * Cminds MultiUserAccounts customer account management model.
 *
 * @package Cminds\MultiUserAccounts\Model
 */
class AccountManagement extends \Magento\Customer\Model\AccountManagement
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * Object initialization.
     *
     * @param CustomerFactory                   $customerFactory
     * @param ManagerInterface                  $eventManager
     * @param StoreManagerInterface             $storeManager
     * @param Random                            $mathRandom
     * @param Validator                         $validator
     * @param ValidationResultsInterfaceFactory $validationResultsDataFactory
     * @param AddressRepositoryInterface        $addressRepository
     * @param CustomerMetadataInterface         $customerMetadataService
     * @param CustomerRegistry                  $customerRegistry
     * @param PsrLogger                         $logger
     * @param Encryptor                         $encryptor
     * @param ConfigShare                       $configShare
     * @param StringHelper                      $stringHelper
     * @param CustomerRepositoryInterface       $customerRepository
     * @param ScopeConfigInterface              $scopeConfig
     * @param TransportBuilder                  $transportBuilder
     * @param DataObjectProcessor               $dataProcessor
     * @param Registry                          $registry
     * @param CustomerViewHelper                $customerViewHelper
     * @param DateTime                          $dateTime
     * @param CustomerModel                     $customerModel
     * @param ObjectFactory                     $objectFactory
     * @param ExtensibleDataObjectConverter     $extensibleDataObjectConverter
     * @param ModuleConfig                      $moduleConfig
     * @param MessageManager                    $messageManager
     */
    public function __construct(
        CustomerFactory $customerFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Random $mathRandom,
        Validator $validator,
        ValidationResultsInterfaceFactory $validationResultsDataFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerMetadataInterface $customerMetadataService,
        CustomerRegistry $customerRegistry,
        PsrLogger $logger,
        Encryptor $encryptor,
        ConfigShare $configShare,
        StringHelper $stringHelper,
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        Registry $registry,
        CustomerViewHelper $customerViewHelper,
        DateTime $dateTime,
        CustomerModel $customerModel,
        ObjectFactory $objectFactory,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        ModuleConfig $moduleConfig,
        MessageManager $messageManager
    ) {
        $this->customerRegistry = $customerRegistry;
        $this->stringHelper = $stringHelper;
        $this->customerRepository = $customerRepository;
        $this->moduleConfig = $moduleConfig;
        $this->messageManager = $messageManager;

        parent::__construct(
            $customerFactory,
            $eventManager,
            $storeManager,
            $mathRandom,
            $validator,
            $validationResultsDataFactory,
            $addressRepository,
            $customerMetadataService,
            $customerRegistry,
            $logger,
            $encryptor,
            $configShare,
            $stringHelper,
            $customerRepository,
            $scopeConfig,
            $transportBuilder,
            $dataProcessor,
            $registry,
            $customerViewHelper,
            $dateTime,
            $customerModel,
            $objectFactory,
            $extensibleDataObjectConverter
        );
    }

    /**
     * {@inheritdoc}
     */
    public function changePassword($email, $curPassword, $newPassword)
    {
        try {
            $customer = $this->customerRepository->get($email);
        } catch (NoSuchEntityException $e) {
            throw new InvalidEmailOrPasswordException(
                __('Invalid login or password.')
            );
        }

        return $this->changePasswordForCustomer($customer, $newPassword);
    }

    /**
     * Change customer password.
     *
     * @param CustomerModel $customer
     * @param string        $newPassword
     *
     * @return bool
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws InputException
     * @throws InvalidEmailOrPasswordException
     */
    private function changePasswordForCustomer($customer, $newPassword)
    {
        $customerSecure = $this->customerRegistry
            ->retrieveSecureData($customer->getId());

        $customerSecure
            ->setRpToken(null)
            ->setRpTokenCreatedAt(null);

        $this->checkPasswordStrength($newPassword);
        $customerSecure->setPasswordHash(
            $this->createPasswordHash($newPassword)
        );

        $this->customerRepository->save($customer);

        return true;
    }

    /**
     * Check if accounts confirmation is required in config.
     *
     * @param   CustomerModel $customer
     *
     * @return  bool
     */
    protected function isConfirmationRequired($customer)
    {
        return (bool)$this->moduleConfig->isConfirmationRequired() && $customer->isConfirmationRequired();
    }

    /**
     * Send either confirmation or welcome email after an account creation.
     *
        return (bool)$this->moduleConfig->isConfirmationRequired() && $customer->is
     * @param CustomerInterface $customerDataObject
     * @param string            $redirectUrl
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendSubaccountEmailConfirmation(
        CustomerInterface $customerDataObject,
        $redirectUrl = ''
    ) {
        /** @var \Magento\Customer\Model\Customer $customerModel */
        $customerModel = $this->customerRegistry
            ->retrieve($customerDataObject->getId())
            ->setConfirmation($this->getRandomConfirmationKey())
            ->save();
        $this->customerRegistry->push($customerModel);

        /** @var CustomerInterface $customerDataObject */
        $customerDataObject = $customerModel->getDataModel();

        try {
            if ($this->isConfirmationRequired($customerModel)) {
                $templateType = self::NEW_ACCOUNT_EMAIL_CONFIRMATION;
            } else {
                $templateType = self::NEW_ACCOUNT_EMAIL_REGISTERED;
            }

            $this->sendNewAccountEmail(
                $customerDataObject,
                $templateType,
                $redirectUrl,
                $customerDataObject->getStoreId()
            );
        } catch (MailException $e) {
            // If we are not able to send a new account email,
            // then inform user about exception.
            $this->messageManager->addExceptionMessage(
                $e,
                __('Email sender is not properly configured in this Magento instance. Please check configuration.')
            );
        }
    }

    /**
     * Generate random confirmation key
     *
     * @return string
     */
    public function getRandomConfirmationKey()
    {
        return md5(uniqid());
    }

    /**
     * @param $customerId
     * @param $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBillingAddressById($customerId, $addressId)
    {
        $customer = $this->customerRepository->getById($customerId);
        return $this->getAddressById($customer, $addressId);
    }
}
