<?php
/**
 * Cminds MultiUserAccounts Request controller.
 *
 * @category Cminds
 * @package  Cminds_MultiUserAccounts
 * @author   Cminds Team <info@cminds.com>
 */
namespace Cminds\MultiUserAccounts\Controller\Order\Approve;

use Cminds\MultiUserAccounts\Api\Data\SubaccountTransportInterface;
use Cminds\MultiUserAccounts\Helper\View as ViewHelper;
use Cminds\MultiUserAccounts\Model\Config as ModuleConfig;
use Cminds\MultiUserAccounts\Model\Service\Order\ApproveRequest as ApproveRequestService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action as ActionController;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Exception\NotFoundException;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;
use \Magento\Quote\Model\QuoteFactory;

class Request extends ActionController
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ApproveRequestService
     */
    private $approveRequestService;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var ProductFactory
     */
    protected $product;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * Request constructor.
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param ViewHelper $viewHelper
     * @param ModuleConfig $moduleConfig
     * @param ApproveRequestService $approveRequestService
     * @param Cart $cart
     * @param ProductFactory $product
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        ViewHelper $viewHelper,
        ModuleConfig $moduleConfig,
        ApproveRequestService $approveRequestService,
        Cart $cart,
        ProductFactory $product,
        QuoteFactory $quoteFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->viewHelper = $viewHelper;
        $this->moduleConfig = $moduleConfig;
        $this->approveRequestService = $approveRequestService;
        $this->cart = $cart;
        $this->product = $product;
        $this->quoteFactory = $quoteFactory;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if ($this->moduleConfig->isEnabled() === false) {
            throw new NotFoundException(__('Extension is disabled.'));
        }

        if ($this->viewHelper->isSubaccountLoggedIn() === false) {
            throw new NotFoundException(__(
                'Only subaccount have permission to view this page.'
            ));
        }

        /** @var SubaccountTransportInterface $subaccountTransportDataObject */
        $subaccountTransportDataObject = $this->customerSession
            ->getSubaccountData();

        $orderApprovalPermission = $subaccountTransportDataObject
            ->getCheckoutOrderApprovalPermission();
        if ((bool)$orderApprovalPermission === false) {
            throw new NotFoundException(__(
                'You don\'t have proper permission to view this page.'
            ));
        }

        return parent::dispatch($request);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /** @var \Magento\Quote\Model\Quote $quoteModel */
        $quoteModel = $this->checkoutSession->getQuote();

        /** @var SubaccountTransportInterface $reqSubaccountTransportDataObject */
        $reqSubaccountTransportDataObject = $this->customerSession
            ->getSubaccountData();

        try {
            $oldQuoteData = $quoteModel;
            $quoteModel
                ->setSubaccountId($reqSubaccountTransportDataObject->getCustomerId())
                ->setIsApproved(0)
                ->setComparePrice($quoteModel->getGrandTotal())
                ->setIsAuthorized(0)
                ->setIsActive(0)
                ->save();

            $items = $oldQuoteData->getAllVisibleItems();
            $newQuote = $this->quoteFactory->create();
            $this->cart->setQuote($newQuote);
            foreach ($items as $item) {
                $productId = $item->getProductId();
                $_product = $this->product->create()->load($productId);

                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                $info = $options['info_buyRequest'];
                $dataObject = new \Magento\Framework\DataObject();
                $dataObject->setData($info);

                $this->cart->addProduct($_product, $dataObject);
            }

            /* Set a flag on the temporary created quote to identify the current customer.
            We are using customer_email field for that.
            Will be used to delete this quote after creating the order. */
            $this->cart->getQuote()
                ->setCustomerEmail($quoteModel->getCustomerEmail());
            $this->cart->save();

            $this->approveRequestService->processNotification($quoteModel);

            $this->messageManager->addSuccessMessage(__(
                'Your order approval request has been sent.'
            ));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__(
                $e->getMessage()
            ));
            /*$this->messageManager->addErrorMessage(__(
                'During order approval request sending something goes wrong.'
            ));*/
        }
        $resultRedirect->setUrl($this->_url->getUrl('checkout/cart/index'));

        return $resultRedirect;
    }
}
