<?php

namespace Cminds\MultiUserAccounts\Model\Plugin\Quote;

use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Cminds\MultiUserAccounts\Api\SubaccountRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Class QuoteAddressValidatorAround
 *
 * @package Cminds\MultiUserAccounts\Model\Plugin\Quote
 */
class QuoteAddressValidatorAround
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * Address factory.
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * Customer repository.
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    protected $subaccountRepository;

    protected $session;

    /**
     * QuoteAddressValidatorAround constructor.
     *
     * @param ModuleConfig $moduleConfig
     * @param ViewHelper $viewHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param SubaccountRepositoryInterface $subaccountRepository
     * @param CustomerSession $session
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        SubaccountRepositoryInterface $subaccountRepository,
        CustomerSession $session
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->subaccountRepository = $subaccountRepository;
        $this->session = $session;
    }

    /**
     * @param \Magento\Quote\Model\QuoteAddressValidator $subject
     * @param callable $proceed
     * @param CartInterface $cart
     * @param AddressInterface $address
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundValidateForCart(\Magento\Quote\Model\QuoteAddressValidator $subject, callable $proceed, CartInterface $cart, AddressInterface $address)
    {
        if ($this->moduleConfig->isEnabled() === false
            || $this->viewHelper->isSubaccountLoggedIn() === false
        ) {
            $proceed($cart, $address);
        } else {
           $this->doValidate($address, $cart->getCustomerIsGuest() ? null : $cart->getCustomer()->getId());
        }
    }

    /**
     * @param AddressInterface $address
     * @param int|null $customerId
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function doValidate(AddressInterface $address, $customerId): void
    {
        $parentId = null;
        $subaccountDataObject = $this->session->getSubaccountData();
        if (!empty($subaccountDataObject) && $subaccountDataObject->getCustomerId()) {
            $customerId = $subaccountDataObject->getCustomerId();
            if (!empty($subaccountDataObject->getParentCustomerId())) {
                $parentId = $subaccountDataObject->getParentCustomerId();
            }
        }
        //validate customer id
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            if (!$customer->getId()) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid customer id %1', $customerId)
                );
            }
        }

        if ($address->getCustomerAddressId()) {
            //Existing address cannot belong to a guest
            if (!$customerId) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
            //Validating address ID
            try {
                $this->addressRepository->getById($address->getCustomerAddressId());
            } catch (NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid address id %1', $address->getId())
                );
            }
            //Finding available customer's addresses
            $applicableAddressIds = array_map(function ($address) {
                /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                return $address->getId();
            }, $this->customerRepository->getById($customerId)->getAddresses());

            if (!empty($parentId)) {
                //Finding available customer's parent addresses
                $parentApplicableAddressIds = array_map(function ($address) {
                    /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                    return $address->getId();
                }, $this->customerRepository->getById($parentId)->getAddresses());

                $applicableAddressIds = array_unique(
                    array_merge($applicableAddressIds, $parentApplicableAddressIds)
                );
            }
            // If address is customer's or parent's - pass validation
            if (!in_array($address->getCustomerAddressId(), $applicableAddressIds)) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
        }
    }
}
