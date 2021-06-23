<?php
/**
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
declare(strict_types=1);

namespace Cminds\MultiUserAccounts\Model\Service\Order;

use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\ResourceModel\Subaccount\CollectionFactory as SubaccountCollectionFactory;
use Cminds\MultiUserAccounts\Api\Data\SubaccountInterface;
use Cminds\MultiUserAccounts\Helper\Email as EmailHelper;
use Magento\Quote\Model\Quote;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;

/**
 * Cminds MultiUserAccounts Approve Request Model.
 *
 * @package Cminds\MultiUserAccounts\Model\Service\Order
 */
class ApproveRequest
{
    /**
     * @var SubaccountTransportRepositoryInterface
     */
    private $subaccountTransportRepository;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @var EmailHelper
     */
    private $emailHelper;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var SubaccountCollectionFactory
     */
    private $subaccountCollectionFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * ApproveRequest constructor.
     *
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepository
     * @param CustomerRegistry $customerRegistry
     * @param ModuleConfig $moduleConfig
     * @param ViewHelper $viewHelper
     * @param EmailHelper $emailHelper
     * @param SubaccountCollectionFactory $subaccountCollectionFactory
     * @param CustomerFactory $customerFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param UrlInterface $urlBuilder
     * @param Session $customerSession
     */
    public function __construct(
        SubaccountTransportRepositoryInterface $subaccountTransportRepository,
        CustomerRegistry $customerRegistry,
        ModuleConfig $moduleConfig,
        ViewHelper $viewHelper,
        EmailHelper $emailHelper,
        SubaccountCollectionFactory $subaccountCollectionFactory,
        CustomerFactory $customerFactory,
        DataObjectFactory $dataObjectFactory,
        UrlInterface $urlBuilder,
        Session $customerSession
    ) {
        $this->subaccountTransportRepository = $subaccountTransportRepository;
        $this->customerRegistry = $customerRegistry;
        $this->moduleConfig = $moduleConfig;
        $this->viewHelper = $viewHelper;
        $this->emailHelper = $emailHelper;
        $this->subaccountCollectionFactory = $subaccountCollectionFactory;
        $this->customerFactory = $customerFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->urlBuilder = $urlBuilder;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Quote $quoteModel
     * @param       $subaccount
     *
     * @return bool
     */
    public function canAuthorize(Quote $quoteModel, $subaccount)
    {
        $authorizers = $this->getAuthorizers($quoteModel);

        // parent account can authorize
        if ($this->getParentAccountId($quoteModel) == $this->customerSession->getCustomerId()) {
            return true;
        }
        
        foreach ($authorizers as $authorizerSubaccount) {
            if ($authorizerSubaccount->getId() === $subaccount->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Quote $quoteModel
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processNotification(Quote $quoteModel)
    {
        $subaccountId = $quoteModel->getSubaccountId();

        /** @var SubaccountTransportInterface $reqSubaccountTransportDataObject */
        $subaccountTransportDataObject = $this->subaccountTransportRepository->getByCustomerId($subaccountId);
        $parentCustomerId = $subaccountTransportDataObject->getParentCustomerId();
        $reqSubaccountName = $this->viewHelper->getSubaccountName($subaccountTransportDataObject);

        /** @var \Magento\Customer\Model\Customer $parentCustomerModel */
        $parentCustomerModel = $this->customerRegistry->retrieve($parentCustomerId);

        $authorizers = $this->getAuthorizers($quoteModel);

        /* does it need authorization for the order before approval? */
        $authorizationRequired = $this->moduleConfig->isOrderApprovalRequestAuthorizationRequired();

        if ($authorizationRequired == true) {
            // Looking for authorizers
            $isAuthorized = (int)$quoteModel->getIsAuthorized();
            if ($isAuthorized == 0) {                   // if quote is not authorized yet
                if (count($authorizers) == 0) {         // if authorizers are absent
                    $isAuthorized = 1;                  // not need to authorize
                } else {
                    ksort($authorizers);
                }
            }
        } else {
            $isAuthorized = 1;                    // does not need in authorization
        }

        $hash = sha1(time() . rand());
        $quoteModel
            ->setApproveHash($hash)
            ->setIsAuthorized($isAuthorized)
            ->save();

        /**
         * Send authorize request notification to authorizers.
         */
        if ($isAuthorized == 0) {
            foreach ($authorizers as $authorizer) {
                $subaccountName = $this->viewHelper->getSubaccountName($authorizer);
                $this->sendOrderApprovalRequest(
                    $subaccountName,
                    $authorizer->getEmail(),
                    $reqSubaccountName,
                    $quoteModel,
                    $hash,
                    $isAuthorized
                );
            }

            return $this;
        }

        /**
         * Send approval request notification to approvers.
         */
        $approvers = $this->getApprovers($quoteModel);
        ksort($approvers);

        /**
         * Send approval request to parent account.
         */
        $sendParentNotification = $this->moduleConfig->shouldParentReceiveAllNotifications();

        if (count($approvers) == 0) {
            $sendParentNotification = true;
        }

        if (count($authorizers) == 0 && count($approvers) == 0) {
            // look for approver on the up level (master)
            $master = $this->getMasterApprover($parentCustomerId);

            if (!$master) {
                $sendParentNotification = false;     // didn't find approver master
            } else {
                $parentCustomerModel = $master;
            }
        }

        if ($sendParentNotification && $isAuthorized == 1) {
            $this->sendOrderApprovalRequest(
                $parentCustomerModel->getName(),
                $parentCustomerModel->getEmail(),
                $reqSubaccountName,
                $quoteModel,
                $hash
            );
        }
    }

    /**
     * @param $parentCustomerId
     * @return bool|\Magento\Customer\Model\Customer
     */
    public function getMasterApprover($parentCustomerId)
    {
        $collection = $this->subaccountCollectionFactory->create();
        $collection
            ->addFieldToFilter('customer_id', $parentCustomerId)
            ->getFirstItem();
        if ($collection->getSize() == 0) {
            // master account
            $customerMaster = $this->customerFactory->create()->load($parentCustomerId);
        } else {
            foreach ($collection as $item) {
                // parent account with permission
                $master = $this->subaccountTransportRepository->getByCustomerId($item->getCustomerId());
                // is parent account has approval permission
                if ($master->getManageOrderApprovalPermission()) {
                    $customerMaster = $this->customerFactory->create()->load($parentCustomerId);
                } else {
                    return false;
                }
            }
        }

        return $customerMaster;
    }

    /**
     * @param Quote $quoteModel
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processAuthorization(Quote $quoteModel)
    {
        $currentSubaccount = $this->customerSession->getSubaccountData();

        $parentCustomerId = $currentSubaccount->getParentCustomerId();
        $SubaccountName = $currentSubaccount->getFirstname() . ' ' . $currentSubaccount->getLastname();

        /** @var \Magento\Customer\Model\Customer $parentCustomerModel */
        $parentCustomerModel = $this->customerRegistry->retrieve($parentCustomerId);

        $isAuthorized = 1;
        $hash = sha1(time() . rand());
        $quoteModel
            ->setApproveHash($hash)
            ->setIsAuthorized($isAuthorized)
            ->save();

        /**
         * Send approval request notification to approvers.
         */
        $approvers = $this->getApprovers($quoteModel);
        ksort($approvers);

        /**
         * Send approval request to parent account.
         */
        $sendParentNotification = $this->moduleConfig->shouldParentReceiveAllNotifications();

        if (count($approvers) == 0) {
            $sendParentNotification = true;
        }

        if ($sendParentNotification && $isAuthorized == 1) {
            $this->sendOrderApprovalRequest(
                $parentCustomerModel->getName(),
                $parentCustomerModel->getEmail(),
                $SubaccountName,
                $quoteModel,
                $hash
            );
        }
    }

    /**
     * @param Quote $quoteModel
     *
     * @return int
     */
    private function getParentAccountId(Quote $quoteModel)
    {
        $subaccountId = $quoteModel->getSubaccountId();

        /** @var SubaccountTransportInterface $reqSubaccountTransportDataObject */
        $reqSubaccountTransportDataObject = $this->subaccountTransportRepository
            ->getByCustomerId($subaccountId);

        $parentCustomerId = $reqSubaccountTransportDataObject
            ->getParentCustomerId();

        return $parentCustomerId;
    }

    /**
     * @param string $approverName
     * @param string $approverEmail
     * @param string $requesterName
     * @param int    $quoteId
     * @param string $hash
     * @param int    $isApproval
     *
     * @return ApproveRequest
     */
    private function sendOrderApprovalRequest(
        $approverName,
        $approverEmail,
        $requesterName,
        $quote,
        $hash,
        $isApproval = 1
    ) {
        $url = 'subaccounts/order/approve';
        if ($isApproval === 0) {
            $url = 'subaccounts/order/authorize';
        }
        $customerAccountUrl = 'customer/account';

        $emailVariablesObject = $this->dataObjectFactory->create();
        $emailVariablesObject->setData([
            'approver_name' => $approverName,
            'requester_name' => $requesterName,
            'customer_account_url' => $this->urlBuilder->getUrl($customerAccountUrl),
            'approve_url' => $this->urlBuilder->getUrl(
                $url,
                ['id' => $quote->getId(), 'hash' => $hash]
            ),
        ]);

        if ($isApproval == 1) {
            $this->emailHelper->sendCheckoutOrderApproveRequestEmail(
                [
                    'name' => $approverName,
                    'email' => $approverEmail,
                ],
                ['data' => $emailVariablesObject]
            );
        } else {
            $this->emailHelper->sendCheckoutOrderAuthorizationRequestEmail(
                [
                    'name' => $approverName,
                    'email' => $approverEmail,
                ],
                ['data' => $emailVariablesObject]
            );
        }

        return $this;
    }

    /**
     * @param Quote $quoteModel
     * @return array
     */
    public function getApprovers(Quote $quoteModel)
    {
        $customerId = $quoteModel->getCustomerId();
        $parentCustomerId = $this->getParentAccountId($quoteModel);
        $quoteGrandTotal = (float)$quoteModel->getBaseGrandTotal();
        $approvers = [];

        /*  1. Searching subbaccounts for approving on the subaccount level */

        /** @var SubaccountCollection $subaccountCollection */
        $subaccountCollection = $this->subaccountCollectionFactory->create()
            ->addFieldToFilter('parent_customer_id', $parentCustomerId)
            ->addFieldToFilter('customer_id', ['neq' => $customerId]);

        foreach ($subaccountCollection as $subaccount) {
            /** @var SubaccountInterface $subaccountData */
            $subaccountTransportData = $this->subaccountTransportRepository->getById($subaccount->getId());

            // Checking permission.
            if (!$subaccountTransportData->getManageOrderApprovalPermission()) {
                continue;
            }

            // Checking allowed approval amount.
            $allowedAmount = (float)$subaccountTransportData
                ->getAdditionalInformationValue('manage_order_approval_permission_amount');

            if ($allowedAmount > 0 && $allowedAmount <= $quoteGrandTotal) {
                continue;
            }

            $approvers[$subaccountTransportData->getId()] = $subaccountTransportData;
        }

        /*  2. Checking parent on approving permission */

        $parentCollection = $this->subaccountCollectionFactory->create()
            ->addFieldToFilter('customer_id', $parentCustomerId);

        if (count($parentCollection) > 0) {
            $parentTransportData = $this->subaccountTransportRepository->getByCustomerId($parentCustomerId);
            // Checking permission
            if ($parentTransportData->getManageOrderApprovalPermission()) {
                // Checking allowed approval amount
                $allowedAmount = (float)$parentTransportData
                    ->getAdditionalInformationValue('manage_order_approval_permission_amount');
                if ($allowedAmount == 0 || $allowedAmount > $quoteGrandTotal) {
                    $approvers[$parentTransportData->getId()] = $parentTransportData;
                }
            }
        }

        return $approvers;
    }

    /**
     * @param Quote $quoteModel
     * @return array
     */
    public function getAuthorizers(Quote $quoteModel)
    {
        $customerId = $quoteModel->getCustomerId();
        $parentCustomerId = $this->getParentAccountId($quoteModel);
        $quoteGrandTotal = (float)$quoteModel->getBaseGrandTotal();
        $authorizers = [];

        /*  1. Searching subbaccounts for authorization on the subaccount level */

        /** @var SubaccountCollection $subaccountCollection */
        $subaccountCollection = $this->subaccountCollectionFactory->create()
            ->addFieldToFilter('parent_customer_id', $parentCustomerId)
            ->addFieldToFilter('customer_id', ['neq' => $customerId]);

        foreach ($subaccountCollection as $subaccount) {
            /** @var SubaccountInterface $subaccountData */
            $subaccountTransportData = $this->subaccountTransportRepository->getById($subaccount->getId());

            // Checking permission.
            if (!$subaccountTransportData->getManageOrderAuthorizePermission()) {
                continue;
            }

            // Checking allowed approval amount.
            $allowedAmount = (float)$subaccountTransportData
                ->getAdditionalInformationValue('manage_order_approval_permission_amount');

            if ($allowedAmount > 0 && $allowedAmount <= $quoteGrandTotal) {
                continue;
            }

            $authorizers[$subaccountTransportData->getId()] = $subaccountTransportData;
        }

        /*  2. Checking parent on authorization permission */

        $parentCollection = $this->subaccountCollectionFactory->create()
            ->addFieldToFilter('customer_id', $parentCustomerId);

        if (count($parentCollection) > 0) {
            $parentTransportData = $this->subaccountTransportRepository->getByCustomerId($parentCustomerId);
            // Checking permission
            if ($parentTransportData->getManageOrderAuthorizePermission()) {
                // Checking allowed approval amount
                $allowedAmount = (float)$parentTransportData
                    ->getAdditionalInformationValue('manage_order_approval_permission_amount');
                if ($allowedAmount == 0 || $allowedAmount > $quoteGrandTotal) {
                    $authorizers[$parentTransportData->getId()] = $parentTransportData;
                }
            }
        }

        return $authorizers;
    }
}
