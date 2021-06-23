<?php

namespace Cminds\MultiUserAccounts\Controller\Order;

use Cminds\MultiUserAccounts\Helper\Email as EmailHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Action\Action as ActionController;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Model\QuoteFactory;

/**
 * Cminds MultiUserAccounts reject controller.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Piotr Pierzak <piotr@cminds.com>
 */
class Reject extends ActionController
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
     * Object initialization.
     *
     * @param Context           $context
     * @param ModuleConfig      $moduleConfig
     * @param EmailHelper       $emailHelper
     * @param CustomerRegistry  $customerRegistry
     * @param QuoteFactory      $quoteFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        EmailHelper $emailHelper,
        CustomerRegistry $customerRegistry,
        QuoteFactory $quoteFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->emailHelper = $emailHelper;
        $this->customerRegistry = $customerRegistry;
        $this->quoteFactory = $quoteFactory;
        $this->dataObjectFactory = $dataObjectFactory;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
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
     *
     * @return ResultRedirect
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

        /** @var \Magento\Quote\Model\Quote $quoteModel */
        $quoteModel = $this->quoteFactory->create()->loadByIdWithoutStore($quoteId);

        if (empty($hash) || !$quoteModel->getId() || ($quoteModel->getApproveHash() !== $hash)) {
            $this->messageManager->addErrorMessage(__('Order approval hash expired or incorrect. This request was declined as invalid.'));

            $quoteModel
                ->setIsApproved(0)
                ->setIsAuthorized(0)
                ->setApproveHash(null)
                ->setSubaccountId(null)
                ->save();

            return $resultRedirect->setPath($failedRedirectUrl);
        }

        $resultRedirect->setUrl($successRedirectUrl);

        if ((int)$quoteModel->getIsApproved() === 1) {
            $this->messageManager->addErrorMessage(
                __('Order request is already approved.')
            );

            return $resultRedirect;
        }

        $reason = $this->getRequest()->getParam('reason');
        if (!$reason) {
            $this->messageManager->addErrorMessage(
                __('Reject reason can not be empty.')
            );

            return $resultRedirect;
        }

        /** @var \Magento\Customer\Model\Customer $customerModel */
        $customerModel = $this->customerRegistry
            ->retrieve($quoteModel->getSubaccountId());

        try {
            $quoteModel
                ->setIsApproved(0)
                ->setIsAuthorized(0)
                ->setApproveHash(null)
                ->setSubaccountId(null)
                ->save();

            $emailVariablesObject = $this->dataObjectFactory->create();
            $emailVariablesObject->setData([
                'requester_name' => $customerModel->getName(),
                'reason' => $reason,
            ]);

            $this->emailHelper->sendCheckoutOrderRejectedEmail(
                [
                    'name' => $customerModel->getName(),
                    'email' => $customerModel->getEmail(),
                ],
                ['data' => $emailVariablesObject]
            );

            $this->messageManager->addSuccessMessage(
                __('Order approval request has been rejected.')
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('During order reject process something goes wrong.')
            );
        }

        return $resultRedirect;
    }
}
