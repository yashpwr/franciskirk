<?php

namespace Cminds\MultiUserAccounts\Controller\Order;

use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Exception\WrongAddressException;
use Cminds\MultiUserAccounts\Helper\Email as EmailHelper;
use Cminds\MultiUserAccounts\Helper\OrderCreate;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action as ActionController;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Model\QuoteFactory;

/**
 * Cminds MultiUserAccounts approve controller.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Approve extends ActionController
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var EmailHelper
     */
    private $emailHelper;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @var OrderCreate
     */
    protected $orderHelper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var SubaccountTransportRepositoryInterface
     */
    protected $subaccountTransportRepositoryInterface;

    /**
     * @param Context $context
     * @param ModuleConfig $moduleConfig
     * @param EmailHelper $emailHelper
     * @param CustomerRegistry $customerRegistry
     * @param QuoteFactory $quoteFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param ViewHelper $viewHelper
     * @param OrderCreate $orderHelper
     * @param Session $customerSession
     * @param SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        EmailHelper $emailHelper,
        CustomerRegistry $customerRegistry,
        QuoteFactory $quoteFactory,
        DataObjectFactory $dataObjectFactory,
        ViewHelper $viewHelper,
        OrderCreate $orderHelper,
        Session $customerSession,
        SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->emailHelper = $emailHelper;
        $this->customerRegistry = $customerRegistry;
        $this->quoteFactory = $quoteFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->viewHelper = $viewHelper;
        $this->orderHelper = $orderHelper;
        $this->customerSession = $customerSession;
        $this->subaccountTransportRepositoryInterface = $subaccountTransportRepositoryInterface;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(RequestInterface $request)
    {
        if ($this->moduleConfig->isEnabled() === false) {
            throw new NotFoundException(__('Extension is disabled.'));
        }

        return parent::dispatch($request);
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $quoteId = $this->getRequest()->getParam('id');
        $hash = $this->getRequest()->getParam('hash');
        $uenc = $this->getRequest()->getParam('uenc');

        if (!empty($uenc)) {
            $decodedUenc = base64_decode($uenc);
            $failedRedirectUrl = $decodedUenc;
            $successRedirectUrl = $decodedUenc;
        } else {
            $failedRedirectUrl = '/';

            $encodedUenc = base64_encode($this->_url->getUrl('/'));
            $successRedirectUrl = $this->_url->getUrl(
                'subaccounts/permission/redirect',
                ['uenc' => $encodedUenc]
            );
        }

        if (empty($quoteId)) {
            return $resultRedirect->setPath($failedRedirectUrl);
        }

        /** @var \Magento\Quote\Model\Quote $quoteModel */
        $quoteModel = $this->quoteFactory->create()->loadByIdWithoutStore($quoteId);
        if (!$quoteModel->getId()) {
            return $resultRedirect->setPath($failedRedirectUrl);
        }

        $resultRedirect->setUrl($successRedirectUrl);

        if ((int)$quoteModel->getIsAuthorized() === 0
            && $this->moduleConfig->isOrderApprovalRequestAuthorizationRequired() === true
            && $this->viewHelper->isSubaccountLoggedIn(true)
        ) {
            $this->messageManager->addErrorMessage(
                __('Order request can not be approved, it requires authorization first.')
            );

            return $resultRedirect;
        }

        if ($this->viewHelper->isSubaccountLoggedIn()) {
            $subaccount = $this->subaccountTransportRepositoryInterface->getByCustomerId(
                $this->customerSession->getCustomerId()
            );
            if (!($subaccount->getManageOrderApprovalPermission() ||
                $subaccount->getCheckoutOrderCreatePermission())
            ) {
                $this->messageManager->addErrorMessage(
                    __('You are not allowed to approve or create orders directly.')
                );

                return $resultRedirect;
            }
        }

        /** @var \Magento\Customer\Model\Customer $customerModel */
        $customerModel = $this->customerRegistry
            ->retrieve($quoteModel->getSubaccountId());

        try {
            if ($this->moduleConfig->createOrderOnApprove()) {
                $orderId = $this->orderHelper->createOrderFromQuote(
                    $quoteModel,
                    $this->moduleConfig->getApprovedOrderShippingMethod(),
                    $this->moduleConfig->getApprovedOrderPaymentMethod(),
                    $this->moduleConfig->isForceUseParentAccountDetailsForOrderEnabled()
                        ? $this->customerSession->getCustomerId() : 0
                );
                $this->messageManager->addSuccessMessage(
                    __("Order #$orderId has been created.")
                );
            } else {
                $this->messageManager->addSuccessMessage(
                    __('Order has been approved.')
                );
            }
            $quoteModel
                ->setIsApproved(1)
                ->setApproveHash(null)
                ->save();
            $emailVariablesObject = $this->dataObjectFactory->create();
            $emailVariablesObject->setData([
                'requester_name' => $customerModel->getName(),
            ]);

            $this->emailHelper->sendCheckoutOrderApprovedEmail(
                [
                    'name' => $customerModel->getName(),
                    'email' => $customerModel->getEmail(),
                ],
                ['data' => $emailVariablesObject]
            );
        } catch (WrongAddressException $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Something gone wrong during order approval.')
            );
        }
        return $resultRedirect;
    }
}
