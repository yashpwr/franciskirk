<?php
/**
 * @category Cminds
 * @package  MultiUserAccounts
 * @author   Cminds Core Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Block\Order;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\Subaccount as SubaccountHelper;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use \Magento\Sales\Block\Order\History as BaseHistory;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

/**
 * Class History
 *
 * @package Cminds\MultiUserAccounts\Block\Order
 */
class History extends BaseHistory
{
    /**
     * @var CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * @var ModuleConfig
     */
    protected $moduleConfig;

    /**
     * @var ViewHelper
     */
    protected $viewHelper;

    /**
     * @var SubaccountHelper
     */
    protected $subaccountHelper;

    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;

    /**
     * History constructor.
     *
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param ModuleConfig $moduleConfig
     * @param ViewHelper $viewHelper
     * @param SubaccountHelper $subaccountHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $orderCollectionFactory,
        Session $customerSession,
        Config $orderConfig,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        SubaccountHelper $subaccountHelper,
        array $data = []
    ) {
        parent::__construct($context,
            $orderCollectionFactory,
            $customerSession,
            $orderConfig,
            $data
        );

        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->customerSession = $customerSession;
        $this->orderConfig = $orderConfig;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->subaccountHelper = $subaccountHelper;
    }

    /**
     * Get customer orders
     *
     * @return bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) { // check if needed
            return false;
        }

        $targetAccountIds = $this->getTargetAccountIds();

        if (!$this->orders) {
            $this->orders = $this->getOrderCollectionFactory()->create()
                ->addFieldToSelect(
                '*'
                )->addFieldToFilter(
                    'status',
                    ['in' => $this->orderConfig->getVisibleOnFrontStatuses()]
                )->setOrder(
                    'created_at',
                    'desc'
                )->addFieldToFilter(
                    'customer_id',
                    ['in' => $targetAccountIds]
                );
        }

        return $this->orders;
    }

    /**
     * Provide order collection factory
     *
     * @return CollectionFactoryInterface
     */
    private function getOrderCollectionFactory()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = ObjectManager::getInstance()->get(CollectionFactoryInterface::class);
        }

        return $this->orderCollectionFactory;
    }

    /**
     * get Ids of customer with related subaccounts
     *
     * @return array
     *
     */
    protected function getTargetAccountIds()
    {
        $parentIds = $targetAccountIds = [];
        $parentCanSeeSubaccountsOrderHistory = (bool)$this->moduleConfig->getParentCanSeeSubaccountsOrderHistory();

        $isSubAccount = (bool)$this->viewHelper->isSubaccountLoggedIn();
        if (true === $isSubAccount) {
            /** @var SubaccountTransportInterface $subaccountTransportDataObject */
            $subaccountTransportDataObject = $this->customerSession
                ->getSubaccountData();
            $customerId = $subaccountTransportDataObject->getCustomerId();
            // if customer can view parent account orders
            // and orders from other sibling subaccounts
            if (true === (bool)$subaccountTransportDataObject->getAccountOrderHistoryViewPermission()
                && true === $parentCanSeeSubaccountsOrderHistory
            ) {
                // add master id to array in order to include master orders too
                $targetAccountIds[] = $parentIds[] = $subaccountTransportDataObject->getParentCustomerId();
            }
        } else {
            $customerId = $this->customerSession->getCustomerId();
        }

        $targetAccountIds[] = $parentIds[] = $customerId;

        // if veiwing child orders is allowed
        if (true === $parentCanSeeSubaccountsOrderHistory) {
            // get all child account ids
            $allSubaccountIds = $this->subaccountHelper->getAllSubaccountIds($customerId);
            $targetAccountIds = array_merge($targetAccountIds, $allSubaccountIds);
        }

        return $targetAccountIds;
    }
}
