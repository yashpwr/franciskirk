<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Observer\Checkout\Quote;

use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\App\RequestInterface;

/**
 * Cminds MultiUserAccounts after order save observer.
 * Will be executed on "checkout_submit_all_after" event.
 *
 * @package Cminds\MultiUserAccounts\Observer\Checkout\Quote
 */
class SubmitAllAfter implements ObserverInterface
{
    /**
     * @var SubaccountTransportRepositoryInterface
     */
    private $subaccountTransportRepository;

    /**
     * @var SubaccountCollectionFactory
     */
    private $subaccountCollectionFactory;

    /**
     * Order sender object.
     *
     * @var OrderSender
     */
    private $orderSender;

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
     * Customer session object.
     *
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Customer factory object.
     *
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * RequestInterface object
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * SubmitAllAfter constructor.
     *
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepository
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     * @param OrderSender $orderSender
     * @param ModuleConfig $moduleConfig
     * @param ViewHelper $viewHelper
     * @param CustomerSession $customerSession
     * @param CustomerFactory $customerFactory
     * @param RequestInterface $request
     */
    public function __construct(
        SubaccountTransportRepositoryInterface $subaccountTransportRepository,
        SubaccountCollectionFactory $subaccountCollectionFactory,
        OrderSender $orderSender,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        CustomerSession $customerSession,
        CustomerFactory $customerFactory,
        RequestInterface $request
    ) {
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->subaccountCollectionFactory = $subaccountCollectionFactory;
        $this->orderSender = $orderSender;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->customerSession = $customerSession;
        $this->customerFactory = $customerFactory;
        $this->request = $request;
    }

    /**
     * @param Observer $observer on checkout_submit_all_after
     *
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        if ($this->moduleConfig->isEnabled() === false) {
            return $this;
        }

        $checkCheckoutProcess = $this->request->getPostValue();

        if (!empty($checkCheckoutProcess)) {                // Checkout with Multiple addresses
            $orders = $observer->getEvent()->getOrders();
            /** @var  \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getEvent()->getQuote();

            if (!$orders && $order = $observer->getEvent()->getOrder()) {
                $orders = [$order];
            }

            foreach($orders as $key=>$order) {

                //@TODO check for nested
                if ($this->viewHelper->isSubaccountLoggedIn() === false) {
                    // code from Magento\Quote\Observer\Webapi\SubmitOrder
                    /**
                     * a flag to set that there will be redirect to third party after confirmation
                     */
                    $redirectUrl = $quote->getPayment()->getOrderPlaceRedirectUrl();

                    // fix for admin order creation - email order confirmation checkbox
                    $requestData = $this->request->getParams();
                    $canSend = true;
                    if (count($requestData) && isset($requestData['order']) && !isset($requestData['order']['send_confirmation'])) {
                        $canSend = false;
                    }

                    if (!$redirectUrl && $order->getCanSendNewEmailFlag() && $canSend) {
                        $this->orderSender->send($order);
                    }

                    return $this;
                }

                $subaccountDataObject = $this->customerSession->getSubaccountData();

                $customerMaster = $this->getParentAccount($subaccountDataObject->getParentCustomerId());

                $customerSubaccount = $this->customerFactory->create()
                    ->load($subaccountDataObject->getCustomerId());

                $notificationConfig = $this->moduleConfig->getNotificationConfig();

                $originalCustomerFirstname = $order->getCustomerFirstname();
                $originalCustomerLastName = $order->getCustomerLastname();
                $originalCustomerEmail = $order->getCustomerEmail();

                switch ($notificationConfig) {
                    case ModuleConfig::NOTIFICATION_MAIN_ACCOUNT:
                        if ($customerMaster) {
                            $order
                                ->setCustomerFirstname($customerMaster->getFirstname())
                                ->setCustomerLastname($customerMaster->getLastname())
                                ->setCustomerEmail($customerMaster->getEmail());
                            $this->orderSender->send($order);
                        }
                        break;
                    case ModuleConfig::NOTIFICATION_SUBACCOUNT:
                        if ($subaccountDataObject->getCheckoutOrderPlacedNotificationPermission()) {
                            $order
                                ->setCustomerFirstname($customerSubaccount->getFirstname())
                                ->setCustomerLastname($customerSubaccount->getLastname())
                                ->setCustomerEmail($customerSubaccount->getEmail());
                            $this->orderSender->send($order);
                        }
                        break;
                    case ModuleConfig::NOTIFICATION_BOTH:
                        if ($customerMaster) {
                            $order
                                ->setCustomerFirstname($customerMaster->getFirstname())
                                ->setCustomerLastname($customerMaster->getLastname())
                                ->setCustomerEmail($customerMaster->getEmail());
                            $this->orderSender->send($order);
                        }

                        if ($subaccountDataObject->getCheckoutOrderPlacedNotificationPermission()) {
                            $order
                                ->setCustomerFirstname($customerSubaccount->getFirstname())
                                ->setCustomerLastname($customerSubaccount->getLastname())
                                ->setCustomerEmail($customerSubaccount->getEmail());
                            $this->orderSender->send($order);
                        }
                        break;
                }

                // rollback original order data
                $order
                    ->setCustomerFirstname($originalCustomerFirstname)
                    ->setCustomerLastname($originalCustomerLastName)
                    ->setCustomerEmail($originalCustomerEmail)
                    ->save();

                $quote
                    ->setIsAuthorized(0)
                    ->setAuthorizedRange(0)
                    ->setIsApproved(0)
                    ->setSubaccountId(null)
                    ->save();

                return $this;
            }
        } else {
            $order = $observer->getEvent()->getOrder();
            /** @var  \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getEvent()->getQuote();

            if ($this->viewHelper->isSubaccountLoggedIn() === false) {
                // code from Magento\Quote\Observer\Webapi\SubmitOrder
                /**
                 * a flag to set that there will be redirect to third party after confirmation
                 */
                $redirectUrl = $quote->getPayment()->getOrderPlaceRedirectUrl();

                // fix for admin order creation - email order confirmation checkbox
                $requestData = $this->request->getParams();
                $canSend = true;
                if (count($requestData) && isset($requestData['order']) && !isset($requestData['order']['send_confirmation'])) {
                    $canSend = false;
                }

                if (!$redirectUrl && $order->getCanSendNewEmailFlag() && $canSend) {
                    $this->orderSender->send($order);
                }

                return $this;
            }

            $subaccountDataObject = $this->customerSession->getSubaccountData();
            $customerMaster = $this->getParentAccount($subaccountDataObject->getParentCustomerId());

            $customerSubaccount = $this->customerFactory->create()
                ->load($subaccountDataObject->getCustomerId());

            $notificationConfig = $this->moduleConfig->getNotificationConfig();

            $originalCustomerFirstname = $order->getCustomerFirstname();
            $originalCustomerLastName = $order->getCustomerLastname();
            $originalCustomerEmail = $order->getCustomerEmail();

            /** send email notification */
            switch ($notificationConfig) {
                case ModuleConfig::NOTIFICATION_MAIN_ACCOUNT:
                    if ($customerMaster) {
                        $order
                            ->setCustomerFirstname($customerMaster->getFirstname())
                            ->setCustomerLastname($customerMaster->getLastname())
                            ->setCustomerEmail($customerMaster->getEmail());
                        $this->orderSender->send($order);
                    }
                    break;
                case ModuleConfig::NOTIFICATION_SUBACCOUNT:
                    if ($subaccountDataObject->getCheckoutOrderPlacedNotificationPermission()) {
                        $order
                            ->setCustomerFirstname($customerSubaccount->getFirstname())
                            ->setCustomerLastname($customerSubaccount->getLastname())
                            ->setCustomerEmail($customerSubaccount->getEmail());
                        $this->orderSender->send($order);
                    }
                    break;
                case ModuleConfig::NOTIFICATION_BOTH:
                    if ($customerMaster) {
                        $order
                            ->setCustomerFirstname($customerMaster->getFirstname())
                            ->setCustomerLastname($customerMaster->getLastname())
                            ->setCustomerEmail($customerMaster->getEmail());
                        $this->orderSender->send($order);
                    }

                    if ($subaccountDataObject->getCheckoutOrderPlacedNotificationPermission()) {
                        $order
                            ->setCustomerFirstname($customerSubaccount->getFirstname())
                            ->setCustomerLastname($customerSubaccount->getLastname())
                            ->setCustomerEmail($customerSubaccount->getEmail());
                        $this->orderSender->send($order);
                    }
                    break;
            }

            // rollback original order data
            $order
                ->setCustomerFirstname($originalCustomerFirstname)
                ->setCustomerLastname($originalCustomerLastName)
                ->setCustomerEmail($originalCustomerEmail)
                ->save();

            $quote
                ->setIsAuthorized(0)
                ->setAuthorizedRange(0)
                ->setIsApproved(0)
                ->setSubaccountId(null)
                ->save();

            return $this;
        }
    }

    /**
     * @param $parentCustomerId
     * @return \Magento\Customer\Model\Customer
     */
    public function getParentAccount($parentCustomerId)
    {
        $collection = $this->subaccountCollectionFactory->create();
        $collection
            ->addFieldToFilter('customer_id', $parentCustomerId)
            ->getFirstItem();
        if ($collection->getSize() == 0) {
            // master account
            return $this->customerFactory->create()->load($parentCustomerId);
        }

        foreach ($collection as $item) {
            $parent = $this->subaccountTransportRepository->getByCustomerId($item->getCustomerId());
            if ($this->moduleConfig->isOrderApprovalRequestAuthorizationRequired()) {
                if ($parent->getManageOrderAuthorizePermission()) {
                    $masterId = $parent->getParentCustomerId();
                    $parentCollection = $this->subaccountCollectionFactory->create();
                    $parentCollection
                        ->addFieldToFilter('customer_id', $masterId)
                        ->getFirstItem();
                    if ($parentCollection->getSize() > 0) {
                        foreach ($parentCollection as $parentItem) {
                            // parent account with permission
                            $master = $this->subaccountTransportRepository->getByCustomerId($parentItem->getCustomerId());
                            // is parent account has approval permission
                            if ($master->getManageOrderApprovalPermission()) {
                                return $this->customerFactory->create()->load($masterId);
                            }
                        }
                    } else {
                        // master account
                        return $this->customerFactory->create()->load($masterId);
                    }
                }
            } else {
                if ($parent->getManageOrderApprovalPermission()) {
                    return $this->customerFactory->create()->load($parent->getCustomerId());
                }
            }
        }

        return false;
    }
}
