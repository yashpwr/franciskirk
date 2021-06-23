<?php

namespace Cminds\MultiUserAccounts\Observer\Customer;

use Cminds\MultiUserAccounts\Helper\Email;
use Cminds\MultiUserAccounts\Model\Config;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;

/**
 * Cminds MultiUserAccounts before customer save observer.
 * Will be executed on "customer_register_success" event.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class RegisterSuccess implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $moduleConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var Email
     */
    protected $emailHelper;

    /**
     * @param Config $moduleConfig
     * @param ManagerInterface $messageManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     * @param RedirectFactory $redirectFactory
     * @param Email $emailHelper
     */
    public function __construct(
        Config $moduleConfig,
        ManagerInterface $messageManager,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        RedirectFactory $redirectFactory,
        Email $emailHelper
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->emailHelper = $emailHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        if ($this->moduleConfig->isEnabled() && $this->moduleConfig->parentAccountsNeedsToBeApproved()) {
            $customer->setCustomAttribute('customer_is_active', 0);
            $this->customerRepository->save($customer);
            $this->customerSession->logout();
            $this->emailHelper->sendCustomerApproveEmail($customer);
            $this->messageManager->addSuccessMessage('Your account has been successfully created.');
            throw new LocalizedException(__('You will be able to log in after the administrator approves your account.'));
        }
        return $this;
    }
}
