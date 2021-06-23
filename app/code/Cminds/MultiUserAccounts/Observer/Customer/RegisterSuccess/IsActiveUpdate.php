<?php

namespace Cminds\MultiUserAccounts\Observer\Customer\RegisterSuccess;

use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Cminds MultiUserAccounts before customer save observer.
 * Will be executed on "customer_register_success" event.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class IsActiveUpdate implements ObserverInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * ManageSubaccountsUpdate constructor.
     *
     * @param ModuleConfig                $moduleConfig
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param Registry                    $coreRegistry
     * @param ViewHelper                  $viewHelper
     * @param CustomerSession $customerSession
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        CustomerRepositoryInterface $customerRepositoryInterface,
        Registry $coreRegistry,
        ViewHelper $viewHelper,
        CustomerSession $customerSession
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->customerRepository = $customerRepositoryInterface;
        $this->viewHelper = $viewHelper;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     *
     * @return IsActiveUpdate
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function execute(Observer $observer)
    {
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === true
        ) {
            return $this;
        }

        /** @var CustomerInterface $customer */
        $customer = $observer->getEvent()->getCustomer();

        $isActive = $customer->getCustomAttribute('customer_is_active');
        if ($isActive === null) {
            $customer->setCustomAttribute('customer_is_active', 1);
            $this->customerRepository->save($customer);
        }

        return $this;
    }
}
