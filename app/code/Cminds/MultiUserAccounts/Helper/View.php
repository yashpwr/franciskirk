<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Helper;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Cminds MultiUserAccounts view helper.
 *
 * @package Cminds\MultiUserAccounts\Helper
 */
class View extends AbstractHelper
{
    /**
     * Session object.
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
     * @var SubaccountTransportRepositoryInterface
     */
    protected $subaccountTransportRepositoryInterface;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var StoreManager
     */
    public $storeManager;

    /**
     * View constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param ModuleConfig $moduleConfig
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface
     * @param CustomerRepository $customerRepository
     * @param CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        ModuleConfig $moduleConfig,
        SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface,
        CustomerRepository $customerRepository,
        CollectionFactory $orderCollectionFactory,
        StoreManager $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->moduleConfig = $moduleConfig;
        $this->subaccountTransportRepositoryInterface = $subaccountTransportRepositoryInterface;
        $this->customerRepository = $customerRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Concatenate all subaccount name parts into full subaccount name.
     *
     * @param SubaccountTransportInterface $subaccountTransportDataObject Subaccount
     *     transport data object.
     *
     * @return string
     */
    public function getSubaccountName(SubaccountTransportInterface $subaccountTransportDataObject)
    {
        return trim(
            ($subaccountTransportDataObject->getPrefix() ? $subaccountTransportDataObject->getPrefix() . ' ' : '')
            . $subaccountTransportDataObject->getFirstname()
            . ($subaccountTransportDataObject->getMiddlename() ? ' ' . $subaccountTransportDataObject->getMiddlename() : '')
            . ' '
            . $subaccountTransportDataObject->getLastname()
            . ($subaccountTransportDataObject->getSuffix() ? ' ' . $subaccountTransportDataObject->getSuffix() : '')
        );
    }

    /**
     * Return bool value depends of that if subaccount is logged in the current session.
     *
     * @param bool|false $skipForNested
     * @return bool
     */
    public function isSubaccountLoggedIn($skipForNested = false)
    {
        /*
         * SubaccountData in session - subaccount object from cminds_multiuseraccounts_subaccount table
         * CustomerData in session - customer object from customer_entity table
         * SubaccountFormData in session - subsubaccountobject object from cminds_multiuseraccounts_subaccount table
         * */
        if ($this->moduleConfig->isNestedSubaccountsAllowed() && $skipForNested) {
            return false;
        }

        /** @var SubaccountTransportInterface $subaccountDataObject */
        $subaccountTransportDataObject = $this->customerSession->getSubaccountData();
        if ($subaccountTransportDataObject === null) {     // if it is a main account
            return false;
        }

        // this is subaccount object
        $subaccountCustomerId = $subaccountTransportDataObject->getCustomerId();

        /** @var CustomerInterface $customerDataObject */
        $customerDataObject = $this->customerSession->getCustomerData();

        // this is customer object
        $customerId = $customerDataObject ? (int)$customerDataObject->getId() : 0;

        if ((int)$subaccountCustomerId === $customerId) {
            return true;
        }

        return false;
    }

    /**
     * Return bool value depends of that if subaccount can manage orders
     * waiting for approval.
     *
     * @return bool
     */
    public function canManageOrderApprovals()
    {
        if (!$this->isSubaccountLoggedIn()) {
            return $this->canManageSubaccounts();
        }

        if ($this->moduleConfig->isNestedSubaccountsAllowed()) {
            /** @var SubaccountTransportInterface $subaccountDataObject */
            $subaccountTransportDataObject = $this->customerSession
                ->getSubaccountData();
            $customerId = $subaccountTransportDataObject->getCustomerId();

            $subaccount = $this->subaccountTransportRepositoryInterface->getByCustomerId($customerId);
            if ($subaccount->getManageOrderApprovalPermission() || $subaccount->getManageOrderAuthorizePermission()) {
                $canManageOrderApprovals = true;
            } else {
                $canManageOrderApprovals = false;
            }
        } else {
            $canManageOrderApprovals = false;
        }

        return $canManageOrderApprovals;
    }

    /**
     * Check if customer can manage subaccounts.
     *
     * @param $customerId
     *
     * @return bool
     */
    public function canManageSubaccounts($customerId = null)
    {
        $customerId = $customerId ?: $this->customerSession->getCustomerId();
        /* var Magento\Customer\Model\ResourceModel\CustomerRepository */
        $customerModel = $this->customerRepository->getById($customerId);

        $canManageSubaccounts = $customerModel->getCustomAttribute('can_manage_subaccounts');
        $canManageSubaccounts = ($canManageSubaccounts) ? $canManageSubaccounts->getValue() : false;

        if ($this->isSubaccountLoggedIn()) {
            if ($this->moduleConfig->isNestedSubaccountsAllowed()) {
                /** @var SubaccountTransportInterface $subaccountDataObject */
                $subaccountTransportDataObject = $this->customerSession
                    ->getSubaccountData();
                $customerId = $subaccountTransportDataObject->getCustomerId();

                $subaccount = $this->subaccountTransportRepositoryInterface->getByCustomerId($customerId);
                $canManageSubaccounts = ($subaccount->getManageSubaccounts()) ? $subaccount->getManageSubaccounts() : false;
            } else {
                $canManageSubaccounts = false;
            }
        }

        return $canManageSubaccounts;
    }

    /**
     * @param $subaccountTransportDataObject
     * @param $quote
     * @return bool
     */
    public function isAllowedToPlaceOrder($subaccountTransportDataObject, $quote)
    {
        /** @var \Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface $subaccountTransportDataObject */
        $orderLimit = (float) $subaccountTransportDataObject
            ->getAdditionalInformationValue($subaccountTransportDataObject::LIMIT_ORDER_TIMES) ?:
            (float)$subaccountTransportDataObject
            ->getAdditionalInformationValue($subaccountTransportDataObject::ORDER_MAX_AMOUNT);

        if ($orderLimit > 0 && (float)$quote->getGrandTotal() > $orderLimit) {
            return false;
        }

        $customerOrders = $this->_getOrdersFromCustomer();

        $limitMonth      = (float) $subaccountTransportDataObject->getAdditionalInformationValue($subaccountTransportDataObject::LIMIT_ORDER_MONTH);
        $ordersThisMonth = $customerOrders->addAttributeToFilter('created_at', ['from' => date('Y-m-01 00:00:00')]);
        if ($limitMonth > 0 && $this->_getOrdersGrandTotalSum($ordersThisMonth) > $limitMonth) {
            return false;
        }

        $limitWeek      = (float) $subaccountTransportDataObject->getAdditionalInformationValue($subaccountTransportDataObject::LIMIT_ORDER_WEEK);
        $ordersThisWeek = $customerOrders->addAttributeToFilter('created_at', ['from' => date('Y-m-d 00:00:00', strtotime('-7 days'))]);
        if ($limitWeek > 0 && $this->_getOrdersGrandTotalSum($ordersThisWeek) > $limitWeek) {
            return false;
        }

        $limitDay      = (float) $subaccountTransportDataObject->getAdditionalInformationValue($subaccountTransportDataObject::LIMIT_ORDER_DAY);
        $ordersThisDay = $customerOrders->addAttributeToFilter('created_at', ['from' => date('Y-m-d 00:00:00')]);
        if ($limitDay > 0 && $this->_getOrdersGrandTotalSum($ordersThisDay) > $limitDay) {
            return false;
        }

        return true;
    }

    /**
     * @param $orders
     * @return float|int
     */
    private function _getOrdersGrandTotalSum($orders)
    {
        $sum = 0;

        if (!$orders->count()) {
            return $sum;
        }

        foreach ($orders as $order) {
            $sum += $order->getData('grand_total');
        }

        return (float) $sum;
    }

    /**
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function _getOrdersFromCustomer()
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToSelect(['subtotal', 'entity_id', 'grand_total', 'status', 'customer_id'])
            ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId());
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }
}
