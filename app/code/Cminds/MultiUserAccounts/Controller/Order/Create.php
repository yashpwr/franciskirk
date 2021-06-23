<?php
/**
 * Cminds MultiUserAccounts Order Create controller.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
namespace Cminds\MultiUserAccounts\Controller\Order;

use Cminds\MultiUserAccounts\Api\SubaccountTransportRepositoryInterface;
use Cminds\MultiUserAccounts\Helper\Email as EmailHelper;
use Cminds\MultiUserAccounts\Helper\OrderCreate;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action as ActionController;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollection;

class Create extends ActionController
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


    private $quoteCollection;

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
     * @var Cart
     */
    protected $cart;

    /**
     * Create constructor.
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
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        ModuleConfig $moduleConfig,
        EmailHelper $emailHelper,
        CustomerRegistry $customerRegistry,
        QuoteFactory $quoteFactory,
        QuoteCollection $quoteCollection,
        DataObjectFactory $dataObjectFactory,
        ViewHelper $viewHelper,
        OrderCreate $orderHelper,
        Session $customerSession,
        SubaccountTransportRepositoryInterface $subaccountTransportRepositoryInterface,
        Cart $cart
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->emailHelper = $emailHelper;
        $this->customerRegistry = $customerRegistry;
        $this->quoteFactory = $quoteFactory;
        $this->quoteCollection = $quoteCollection;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->viewHelper = $viewHelper;
        $this->orderHelper = $orderHelper;
        $this->customerSession = $customerSession;
        $this->subaccountTransportRepositoryInterface = $subaccountTransportRepositoryInterface;
        $this->cart = $cart;
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

        if (!empty($uenc)) {
            $decodedUenc = base64_decode($uenc);
            $failedRedirectUrl = $decodedUenc;
            $successRedirectUrl = $decodedUenc;
        } else {
            $failedRedirectUrl = '/';

            $encodedUenc = base64_encode($this->_url->getUrl('/'));
            $successRedirectUrl = $this->_url->getUrl(
                'checkout/index/index',
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

        if ((int)$quoteModel->getIsAuthorized() === 0) {
            $this->messageManager->addErrorMessage(
                __('You need to be authorized to create order.')
            );

            return $resultRedirect;
        }

        if ((int)$quoteModel->getIsApproved() === 0) {
            $this->messageManager->addErrorMessage(
                __('This order is not approved.')
            );

            return $resultRedirect;
        }

        if (!empty($this->customerSession->getCustomerId())
            && (int)$this->customerSession->getCustomerId() !== (int)$quoteModel->getSubaccountId()) {
            $this->messageManager->addErrorMessage(
                __('You are not allowed to create this order.')
            );

            return $resultRedirect;
        }

        $currentQuote = $this->cart->getQuote();
        try {
            // delete active customer quote
            $currentQuote->delete();
            // delete temporary created quote of this customer identified by customer_email field for that
            $quotes = $this->quoteCollection ->create()
                ->addFieldToFilter('customer_email', $quoteModel->getCustomerEmail())
                ->addFieldToFilter('customer_id', array('null' => true))
                ->addFieldToFilter('subaccount_id', array('null' => true));

            foreach($quotes as $quote) {
                $quote->delete();
            }

            // set active customer approved quote
            $this->cart->setQuote($quoteModel)->save();
            $quoteModel
                ->setIsActive(1)
                ->save();

            return $resultRedirect;
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                __('Can not delete current cart items. Please try again later.')
            );
        }

        return $resultRedirect;
    }
}
