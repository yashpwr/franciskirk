<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Observer\Checkout\Quote;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;

/**
 * Cminds MultiUserAccounts quote submit before observer.
 * Will be executed on "checkout_submit_before" event.
 *
 * @package Cminds\MultiUserAccounts\Observer\Checkout\Quote
 */
class SubmitBefore implements ObserverInterface
{
    const CMINDS_MULTIUSERACCOUNTS_CHANGE_TEMP_USER_ID = 'cminds_multiuseraccounts_change_temp_user_id';

    /**
     * Customer session object.
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Module config object.
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * View helper object.
     *
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * Checkout session object.
     *
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Address repository object.
     *
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var AddressFactory
     */
    private $quoteAddressFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * Object initialization.
     *
     * @param CustomerSession             $customerSession
     * @param ModuleConfig                $moduleConfig
     * @param ViewHelper                  $viewHelper
     * @param CheckoutSession             $checkoutSession
     * @param CustomerFactory             $customerFactory
     * @param AddressRepositoryInterface  $addressRepository
     * @param AddressFactory              $quoteAddressFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param Registry                    $registry
     */
    public function __construct(
        CustomerSession $customerSession,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        CheckoutSession $checkoutSession,
        CustomerFactory $customerFactory,
        AddressRepositoryInterface $addressRepository,
        AddressFactory $quoteAddressFactory,
        CustomerRepositoryInterface $customerRepository,
        Registry $registry
    ) {
        $this->customerSession = $customerSession;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->checkoutSession = $checkoutSession;
        $this->customerFactory = $customerFactory;
        $this->addressRepository = $addressRepository;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
    }

    /**
     * Quote submit before event handler.
     *
     * @param Observer $observer Observer object.
     *
     * @return SubmitBefore
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            return $this;
        }

        /** @var SubaccountTransportInterface $subaccountTransportDataObject */
        $subaccountTransportDataObject = $this->customerSession
            ->getSubaccountData();
        $quoteModel = $this->checkoutSession->getQuote();

        $checkoutOrderApprovalPermission = (bool)$subaccountTransportDataObject
            ->getCheckoutOrderApprovalPermission();

        $pass = false;
        if ($checkoutOrderApprovalPermission === true
            && (int)$quoteModel->getIsApproved() === 1
        ) {
            $pass = true;
        }

        $checkoutOrderCreatePermission = (bool)$subaccountTransportDataObject
            ->getCheckoutOrderCreatePermission();
        if ($pass === false && $checkoutOrderCreatePermission === true) {
            $pass = true;
        }

        if ($pass === false) {
            throw new LocalizedException(
                __('You don\'t have permission to create order.')
            );
        }

        /**
         * Set customer details to the quote depending on configuration
         * for force use parent account details.
         *
         */
        $quote = $observer->getQuote();

        $subAccountMaxAllowedOrderAmount = (float) $subaccountTransportDataObject
            ->getAdditionalInformationValue($subaccountTransportDataObject::ORDER_MAX_AMOUNT);

        if ($subAccountMaxAllowedOrderAmount > 0 && $quote->getSubTotal() > $subAccountMaxAllowedOrderAmount) {
            throw new LocalizedException(
                __('Order amount is greater than the approved order amount.')
            );
        }

        $forceCompanyName = (bool)($subaccountTransportDataObject->getForceUsageParentCompanyNamePermission()
            || $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
        );
        $forceVat = (bool)($subaccountTransportDataObject->getForceUsageParentVatPermission()
            || $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
        );
        $forceAddresses = (bool)($subaccountTransportDataObject->getForceUsageParentAddressesPermission()
            || $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
        );

        if ($forceCompanyName === false
            && $forceVat === false
            && $forceAddresses === false
        ) {
            return $this;
        }

        /** @var Customer $parentCustomer */
        $parentCustomer = $this->customerFactory->create()
            ->load($subaccountTransportDataObject->getParentCustomerId());

        /** @var Address $parentBillingAddress */
        $parentBillingAddress = $parentCustomer->getDefaultBillingAddress();

        /** @var Quote $quote */
        $quote = $observer->getQuote();

        if ($forceVat) {
            $quote->getCustomer()->setTaxvat($parentCustomer->getTaxvat());
            $quote->setCustomerTaxvat($parentCustomer->getTaxvat());
        }

        if ($forceAddresses === false) {
            foreach ($quote->getAllAddresses() as $address) {
                if ($forceCompanyName && $parentBillingAddress !== false) {
                    $address->setCompany($parentBillingAddress->getCompany());
                }
                if ($forceVat) {
                    $address->setVatId($parentBillingAddress->getVatId());
                }
            }
        } else {
            if ($parentBillingAddress === false) {
                throw new LocalizedException(
                    __(
                        'Your permissions configuration force you to use parent '
                        . ' account billing address, but unfortunately he does '
                        . 'not have any.'
                    )
                );
            }

            $parentShippingAddress = $parentCustomer->getDefaultShippingAddress();
            if ($parentShippingAddress === false) {
                throw new LocalizedException(
                    __(
                        'Your permissions configuration force you to use parent '
                        . ' account shipping address, but unfortunately he does '
                        . 'not have any.'
                    )
                );
            }

            /** @var AddressInterface $parentBillingAddress */
            $parentBillingAddress = $this->addressRepository
                ->getById($parentBillingAddress->getId());
            $parentBillingAddress = $this->quoteAddressFactory->create()
                ->importCustomerAddressData($parentBillingAddress);

            $parentShippingAddress = $this->addressRepository
                ->getById($parentShippingAddress->getId());
            $parentShippingAddress = $this->quoteAddressFactory->create()
                ->importCustomerAddressData($parentShippingAddress);

            $quote->setBillingAddress($parentBillingAddress);
            $quote->setShippingAddress($parentShippingAddress);
        }

        return $this;
    }
}
