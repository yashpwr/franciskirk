<?php

namespace Cminds\MultiUserAccounts\Controller\Plugin;

use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\Order;

/**
 * Cminds MultiUserAccounts order view authorization plugin.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class OrderViewAuthorization
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
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var SubaccountCollectionFactory
     */
    private $subaccountCollectionFactory;

    /**
     * OrderViewAuthorization constructor.
     *
     * @param ModuleConfig                $moduleConfig
     * @param CustomerSession             $customerSession
     * @param ViewHelper                  $viewHelper
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        CustomerSession $customerSession,
        ViewHelper $viewHelper,
        SubaccountCollectionFactory $subaccountCollectionFactory
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->customerSession = $customerSession;
        $this->viewHelper = $viewHelper;
        $this->subaccountCollectionFactory = $subaccountCollectionFactory;
    }

    /**
     * This method is responsible for order view authorization in store front.
     *
     * @param \Magento\Sales\Controller\AbstractController\OrderViewAuthorization $subject
     * @param callable                                                            $proceed
     * @param Order                                                               $order
     *
     * @return bool
     */
    public function aroundCanView(
        \Magento\Sales\Controller\AbstractController\OrderViewAuthorization $subject,
        callable $proceed,
        $order
    ) {
        $result = $proceed($order);

        if ($this->moduleConfig->isEnabled() === false) {
            return $result;
        }

        $orderCustomerId = (int)$order->getCustomerId();
        $loggedInCustomerId = (int)$this->customerSession->getCustomerId();
        $parentCanSeeSubaccountsOrderHistory = (bool)$this->moduleConfig->getParentCanSeeSubaccountsOrderHistory();
        $canViewOrder = false;
        $targetParentIds = [];
        
        if ($this->viewHelper->isSubaccountLoggedIn() === true) {

            $subaccountTransportDataObject = $this->customerSession
                ->getSubaccountData();
            $loggedInCustomerId = $subaccountTransportDataObject->getCustomerId();
            if (true === (bool)$subaccountTransportDataObject->getAccountOrderHistoryViewPermission()
                && true === $parentCanSeeSubaccountsOrderHistory
            ) {
                $targetParentIds[] = (int)$subaccountTransportDataObject->getParentCustomerId();
            }
        }
        
        $targetParentIds[] = $loggedInCustomerId;

        /**
        * If order belongs to current customer account then allow access.
        */
        if ($orderCustomerId === $loggedInCustomerId) {
            return true;
        }

        // if viewing child account order history is allowed
        if (true === $parentCanSeeSubaccountsOrderHistory) {
            // check current parent account
            foreach ($targetParentIds as $parentId) {
                if ($orderCustomerId === $parentId) {
                    return true;
                }
            }

            // check own children and siblings
            $subaccountsCollection = $this->subaccountCollectionFactory
                    ->create()
                    ->filterByParentCustomerId($targetParentIds)
                    ->getItems();

            foreach ($subaccountsCollection as $subaccount) {
                $subaccountId = (int)$subaccount->getCustomerId();
                /**
                 * If order belongs to any of parent account subaccounts allow.
                 */
                if ($subaccountId === $orderCustomerId) {
                    $canViewOrder = true;
                    break;
                }
            }
        }

        return $canViewOrder;
    }
}
